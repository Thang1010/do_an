<?php

namespace App\Services;

use App\Models\DonHang;
use App\Models\LichSuKho;
use App\Models\NguyenLieu;
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
        $usage = $this->ingredientUsageForOrder($order->id);

        if (empty($usage)) {
            return;
        }

        $stocks = $this->currentStocksForIngredients(array_keys($usage));
        $shortages = [];

        foreach ($usage as $ingredientId => $data) {
            $required = (float) $data['qty'];
            $available = (float) ($stocks[$ingredientId] ?? 0);

            if ($required > $available + 0.00001) {
                $shortages[] = sprintf(
                    '%s (cần %.2f, còn %.2f)',
                    $data['name'],
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

        foreach ($usage as $ingredientId => $data) {
            $required = (float) $data['qty'];

            if ($required <= 0) {
                continue;
            }

            LichSuKho::create([
                'nguyen_lieu_id' => (int) $ingredientId,
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
     * Kiểm tra tồn kho cho đơn hàng mà KHÔNG ghi gì vào kho.
     *
     * Dùng cho pre-check khi khách ấn "Thanh toán": nếu thiếu nguyên liệu thì
     * chặn tạo link thanh toán và báo "sản phẩm đã hết hàng".
     *
     * @return string[] Danh sách mô tả nguyên liệu thiếu (rỗng = đủ kho).
     */
    public function checkStockForOrder(DonHang $order): array
    {
        $usage = $this->ingredientUsageForOrder($order->id);
        if (empty($usage)) {
            return [];
        }

        $stocks = $this->currentStocksForIngredients(array_keys($usage));
        $shortages = [];

        foreach ($usage as $ingredientId => $data) {
            $required = (float) $data['qty'];
            $available = (float) ($stocks[$ingredientId] ?? 0);

            if ($required > $available + 0.00001) {
                $shortages[] = sprintf(
                    '%s (cần %.2f, còn %.2f)',
                    $data['name'],
                    $required,
                    $available
                );
            }
        }

        return $shortages;
    }

    /**
     * Xuất kho nguyên liệu cho đơn KHÁCH HÀNG khi thanh toán THÀNH CÔNG.
     *
     * Khác với exportIngredientsForOrder(): KHÔNG kiểm tra/không throw khi thiếu kho,
     * vì tiền đã vào PayOS rồi — trường hợp đua mili-giây hi hữu thì cho phép tồn kho
     * xuống âm, và xử lý bằng quy trình hoàn tiền + xóa đơn thủ công (xóa đơn sẽ hoàn kho).
     */
    public function exportIngredientsForPaidOrder(DonHang $order): void
    {
        $usage = $this->ingredientUsageForOrder($order->id);

        foreach ($usage as $ingredientId => $data) {
            $required = (float) $data['qty'];

            if ($required <= 0) {
                continue;
            }

            LichSuKho::create([
                'nguyen_lieu_id' => (int) $ingredientId,
                'loai_giao_dich' => 'xuất kho',
                'don_hang_id' => $order->id,
                'so_luong' => $required,
                'nguoi_tao_id' => Auth::id(),
                'ghi_chu' => 'Xuất theo thanh toán thành công đơn hàng #' . $order->id,
                'created_at' => now(),
            ]);
        }
    }

    /**
     * Tính lượng nguyên liệu sử dụng cho đơn hàng.
     *
     * Bao gồm cả:
     * - Nguyên liệu theo CÔNG THỨC của sản phẩm (so_luong_can * số lượng).
     * - "Nguyên liệu tự thân" của sản phẩm KHÔNG công thức (mỗi sản phẩm = 1 đơn vị
     *   nguyên liệu trong kho) — chỉ tính khi nguyên liệu đó đang sử dụng.
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

        // Sản phẩm KHÔNG công thức: trừ kho chính nguyên liệu tự thân của nó.
        $selfUsage = DB::table('chi_tiet_don_hang')
            ->join('nguyen_lieu', 'nguyen_lieu.san_pham_id', '=', 'chi_tiet_don_hang.san_pham_id')
            ->where('chi_tiet_don_hang.don_hang_id', $orderId)
            ->where('nguyen_lieu.trang_thai', NguyenLieu::TRANG_THAI_DANG_DUNG)
            ->select(
                'nguyen_lieu.id as nguyen_lieu_id',
                'nguyen_lieu.ten_nguyen_lieu',
                DB::raw('SUM(chi_tiet_don_hang.so_luong) as tong_so_luong')
            )
            ->groupBy('nguyen_lieu.id', 'nguyen_lieu.ten_nguyen_lieu')
            ->get();

        foreach ($selfUsage as $item) {
            $id = (int) $item->nguyen_lieu_id;
            $qty = (float) $item->tong_so_luong;
            // Cộng dồn phòng trường hợp hi hữu vừa có công thức vừa có self-ingredient.
            $result[$id] = [
                'qty' => ($result[$id]['qty'] ?? 0) + $qty,
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
