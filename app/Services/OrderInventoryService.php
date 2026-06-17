<?php

namespace App\Services;

use App\Models\DonHang;
use App\Models\LichSuKho;
use App\Traits\CalculatesStock;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Service xử lý nghiệp vụ kho nguyên liệu liên quan đến đơn hàng.
 *
 * Tập trung logic: xuất kho nguyên liệu theo đơn, tính usage, apply delta khi cập nhật đơn.
 */
class OrderInventoryService
{
    use CalculatesStock;

    /**
     * Xuất kho nguyên liệu cho đơn hàng khi xác nhận.
     */
    public function exportIngredientsForOrder(DonHang $order): void
    {
        $ingredientUsage = DB::table('chi_tiet_don_hang')
            ->join('cong_thuc_san_pham', 'cong_thuc_san_pham.san_pham_id', '=', 'chi_tiet_don_hang.san_pham_id')
            ->join('nguyen_lieu', 'nguyen_lieu.id', '=', 'cong_thuc_san_pham.nguyen_lieu_id')
            ->where('chi_tiet_don_hang.don_hang_id', $order->id)
            ->select(
                'cong_thuc_san_pham.nguyen_lieu_id',
                'nguyen_lieu.ten_nguyen_lieu',
                DB::raw('SUM(cong_thuc_san_pham.so_luong_can * chi_tiet_don_hang.so_luong) as tong_so_luong')
            )
            ->groupBy('cong_thuc_san_pham.nguyen_lieu_id', 'nguyen_lieu.ten_nguyen_lieu')
            ->get();

        if ($ingredientUsage->isEmpty()) {
            return;
        }

        $stocks = $this->currentStocksForIngredients($ingredientUsage->pluck('nguyen_lieu_id')->all());
        $shortages = [];

        foreach ($ingredientUsage as $item) {
            $required = (float) $item->tong_so_luong;
            $available = (float) ($stocks[(int) $item->nguyen_lieu_id] ?? 0);

            if ($required > $available + 0.00001) {
                $shortages[] = sprintf(
                    '%s (cần %.2f, còn %.2f)',
                    $item->ten_nguyen_lieu,
                    $required,
                    $available
                );
            }
        }

        if (!empty($shortages)) {
            throw ValidationException::withMessages([
                'trang_thai' => 'Không đủ tồn kho để xác nhận đơn. Thiếu: ' . implode('; ', $shortages),
            ]);
        }

        foreach ($ingredientUsage as $item) {
            $required = (float) $item->tong_so_luong;

            if ($required <= 0) {
                continue;
            }

            LichSuKho::create([
                'nguyen_lieu_id' => (int) $item->nguyen_lieu_id,
                'loai_giao_dich' => 'xuất kho',
                'don_hang_id' => $order->id,
                'so_luong' => $required,
                'nguoi_tao_id' => Auth::id(),
                'ghi_chu' => 'Xuất theo xác nhận đơn hàng #' . $order->id,
                'created_at' => now(),
            ]);
        }
    }

    /**
     * Tính lượng nguyên liệu sử dụng cho đơn hàng.
     */
    public function ingredientUsageForOrder(int $orderId): array
    {
        $usage = DB::table('chi_tiet_don_hang')
            ->join('cong_thuc_san_pham', 'cong_thuc_san_pham.san_pham_id', '=', 'chi_tiet_don_hang.san_pham_id')
            ->join('nguyen_lieu', 'nguyen_lieu.id', '=', 'cong_thuc_san_pham.nguyen_lieu_id')
            ->where('chi_tiet_don_hang.don_hang_id', $orderId)
            ->select(
                'cong_thuc_san_pham.nguyen_lieu_id',
                'nguyen_lieu.ten_nguyen_lieu',
                DB::raw('SUM(cong_thuc_san_pham.so_luong_can * chi_tiet_don_hang.so_luong) as tong_so_luong')
            )
            ->groupBy('cong_thuc_san_pham.nguyen_lieu_id', 'nguyen_lieu.ten_nguyen_lieu')
            ->get();

        $result = [];
        foreach ($usage as $item) {
            $result[(int) $item->nguyen_lieu_id] = [
                'qty' => (float) $item->tong_so_luong,
                'name' => $item->ten_nguyen_lieu,
            ];
        }

        return $result;
    }

    /**
     * Áp dụng chênh lệch nguyên liệu khi cập nhật đơn hàng.
     */
    public function applyIngredientDelta(array $oldUsage, array $newUsage, int $orderId): void
    {
        $ingredientIds = array_unique(array_merge(array_keys($oldUsage), array_keys($newUsage)));
        if (empty($ingredientIds)) {
            return;
        }

        $stocks = $this->currentStocksForIngredients($ingredientIds);
        $shortages = [];
        $deltaMap = [];

        foreach ($ingredientIds as $ingredientId) {
            $oldQty = (float) ($oldUsage[$ingredientId]['qty'] ?? 0);
            $newQty = (float) ($newUsage[$ingredientId]['qty'] ?? 0);
            $delta = $newQty - $oldQty;

            if (abs($delta) < 0.00001) {
                continue;
            }

            $deltaMap[$ingredientId] = $delta;

            if ($delta > 0) {
                $available = (float) ($stocks[$ingredientId] ?? 0);
                if ($delta > $available + 0.00001) {
                    $name = $newUsage[$ingredientId]['name']
                        ?? $oldUsage[$ingredientId]['name']
                        ?? ('Nguyên liệu #' . $ingredientId);
                    $shortages[] = sprintf(
                        '%s (cần thêm %.2f, còn %.2f)',
                        $name,
                        $delta,
                        $available
                    );
                }
            }
        }

        if (!empty($shortages)) {
            throw ValidationException::withMessages([
                'items' => 'Không đủ tồn kho để cập nhật đơn. Thiếu: ' . implode('; ', $shortages),
            ]);
        }

        foreach ($deltaMap as $ingredientId => $delta) {
            if ($delta > 0) {
                LichSuKho::create([
                    'nguyen_lieu_id' => (int) $ingredientId,
                    'loai_giao_dich' => 'xuất kho',
                    'don_hang_id' => $orderId,
                    'so_luong' => $delta,
                    'nguoi_tao_id' => Auth::id(),
                    'ghi_chu' => 'Xuất bổ sung do cập nhật đơn hàng #' . $orderId,
                    'created_at' => now(),
                ]);
                continue;
            }

            LichSuKho::create([
                'nguyen_lieu_id' => (int) $ingredientId,
                'loai_giao_dich' => 'điều chỉnh',
                'don_hang_id' => $orderId,
                'so_luong' => abs($delta),
                'nguoi_tao_id' => Auth::id(),
                'ghi_chu' => 'Hoàn kho do cập nhật đơn hàng #' . $orderId,
                'created_at' => now(),
            ]);
        }
    }

    /**
     * Hoàn lại nguyên liệu vào kho khi xóa/hủy đơn hàng.
     */
    public function restoreIngredientsForOrder(DonHang $order): void
    {
        $usage = $this->ingredientUsageForOrder($order->id);
        foreach ($usage as $ingredientId => $data) {
            if ($data['qty'] > 0) {
                LichSuKho::create([
                    'nguyen_lieu_id' => (int) $ingredientId,
                    'loai_giao_dich' => 'điều chỉnh',
                    'don_hang_id' => $order->id,
                    'so_luong' => $data['qty'],
                    'nguoi_tao_id' => Auth::id(),
                    'ghi_chu' => 'Hoàn kho do hủy/xóa đơn hàng #' . $order->id,
                    'created_at' => now(),
                ]);
            }
        }
    }
}
