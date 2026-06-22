<?php

namespace App\Observers;

use App\Mail\ReviewAlertMail;
use App\Models\CaLamViec;
use App\Models\DanhGiaSanPham;
use App\Models\NguoiDung;
use App\Services\HuggingFaceService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class DanhGiaSanPhamObserver
{
    /**
     * Handle the DanhGiaSanPham "created" event.
     */
    public function created(DanhGiaSanPham $danhGiaSanPham): void
    {
        $this->phanTichCamXuc($danhGiaSanPham);
    }

    /**
     * Handle the DanhGiaSanPham "updated" event.
     */
    public function updated(DanhGiaSanPham $danhGiaSanPham): void
    {
        // Nếu người dùng sửa lại nội dung đánh giá thì phân tích lại
        if ($danhGiaSanPham->wasChanged('noi_dung')) {
            $this->phanTichCamXuc($danhGiaSanPham);
        }
    }

    private function phanTichCamXuc(DanhGiaSanPham $danhGiaSanPham)
    {
        if (empty($danhGiaSanPham->noi_dung)) {
            return;
        }

        try {
            $ai = new HuggingFaceService();
            $ketQua = $ai->analyzeSentiment($danhGiaSanPham->noi_dung);

            if ($ketQua && is_array($ketQua) && isset($ketQua[0])) {
                // Sắp xếp giảm dần theo điểm score để lấy nhãn cao nhất
                usort($ketQua[0], function ($a, $b) {
                    return $b['score'] <=> $a['score'];
                });

                $nhanCaoNhat = $ketQua[0][0]['label']; // VD: 'NEG', 'POS', 'NEU'

                $camXuc = 'Chưa phân tích';
                if ($nhanCaoNhat === 'POS') {
                    $camXuc = 'Tích cực';
                } elseif ($nhanCaoNhat === 'NEG') {
                    $camXuc = 'Tiêu cực';
                } elseif ($nhanCaoNhat === 'NEU') {
                    $camXuc = 'Trung lập';
                }

                // Cập nhật vào Database mà không kích hoạt lại sự kiện update (để tránh vòng lặp vô hạn)
                DanhGiaSanPham::withoutEvents(function () use ($danhGiaSanPham, $camXuc) {
                    $danhGiaSanPham->update(['phan_tich_cam_xuc' => $camXuc]);
                });

                // Đánh giá tiêu cực / trung lập → email cho quản lý ca hiện tại + chủ cửa hàng
                if (in_array($camXuc, ['Tiêu cực', 'Trung lập'], true)) {
                    $this->guiEmailCanhBao($danhGiaSanPham, $camXuc);
                }
            }
        } catch (\Exception $e) {
            Log::error("Lỗi phân tích cảm xúc đánh giá ID {$danhGiaSanPham->id}: " . $e->getMessage());
        }
    }

    /**
     * Gửi email cảnh báo đánh giá tiêu cực/trung lập cho:
     *  - Quản lý phụ trách CA đã bán đơn hàng chứa sản phẩm được đánh giá
     *    (xác định theo thời điểm tạo đơn hàng).
     *  - Tất cả tài khoản "chủ cửa hàng" (luôn luôn nhận).
     */
    private function guiEmailCanhBao(DanhGiaSanPham $review, string $camXuc): void
    {
        try {
            $review->loadMissing(['sanPham', 'nguoiDung', 'donHang']);

            // Thời điểm bán đơn hàng chứa sản phẩm được đánh giá → xác định ca → quản lý phụ trách.
            // Nếu đánh giá không gắn đơn hàng thì dùng thời điểm tạo đánh giá để dò ca.
            $ref = $review->donHang?->created_at ?? $review->created_at ?? now();

            // Quản lý có ca làm việc bao trùm thời điểm bán đơn
            $managerIds = CaLamViec::query()
                ->whereDate('ngay_lam', $ref->toDateString())
                ->where('gio_bat_dau', '<=', $ref->toTimeString())
                ->where('gio_ket_thuc', '>=', $ref->toTimeString())
                ->whereHas('nguoiDung', fn ($q) => $q->where('vai_tro', 'quản lý'))
                ->pluck('nguoi_dung_id')
                ->unique();

            $recipients = NguoiDung::query()
                ->where(function ($q) use ($managerIds) {
                    $q->where('vai_tro', 'chủ cửa hàng');
                    if ($managerIds->isNotEmpty()) {
                        $q->orWhereIn('id', $managerIds);
                    }
                })
                ->whereNotNull('email')
                ->where('email', '!=', '')
                ->get();

            $daGui = [];
            foreach ($recipients as $user) {
                $email = mb_strtolower(trim((string) $user->email));
                if ($email === '' || isset($daGui[$email])) {
                    continue;
                }
                $daGui[$email] = true;

                Mail::to($user->email)->send(new ReviewAlertMail($review, $camXuc));
            }
        } catch (\Throwable $e) {
            Log::error("Lỗi gửi email cảnh báo đánh giá ID {$review->id}: " . $e->getMessage());
        }
    }
}
