<div style="font-family: sans-serif; line-height: 1.6; color: #333; max-width: 520px;">
    <p>Xin chào <strong>{{ $user->ho_ten ?? 'bạn' }}</strong>,</p>
    <p>Ca làm việc của bạn tại <strong>XM COFFEE</strong> vừa được cập nhật. Dưới đây là thông tin chi tiết:</p>

    <div style="display: flex; gap: 12px; margin-bottom: 16px;">
        {{-- Ca cũ --}}
        <div style="flex: 1; padding: 12px; background-color: #fef2f2; border: 1px solid #fecaca; border-radius: 8px;">
            <div style="font-weight: 700; color: #b91c1c; margin-bottom: 8px;">CA CŨ</div>
            <div style="margin-bottom: 4px;">Tên ca: <strong>{{ $oldShift['ten_ca'] }}</strong></div>
            <div style="margin-bottom: 4px;">Ngày làm: <strong>{{ \Carbon\Carbon::parse($oldShift['ngay_lam'])->format('d/m/Y') }}</strong></div>
            <div style="margin-bottom: 4px;">Giờ bắt đầu: <strong>{{ $oldShift['gio_bat_dau'] }}</strong></div>
            <div>Giờ kết thúc: <strong>{{ $oldShift['gio_ket_thuc'] }}</strong></div>
        </div>

        {{-- Ca mới --}}
        <div style="flex: 1; padding: 12px; background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px;">
            <div style="font-weight: 700; color: #15803d; margin-bottom: 8px;">CA MỚI</div>
            <div style="margin-bottom: 4px;">Tên ca: <strong>{{ $newShift['ten_ca'] }}</strong></div>
            <div style="margin-bottom: 4px;">Ngày làm: <strong>{{ \Carbon\Carbon::parse($newShift['ngay_lam'])->format('d/m/Y') }}</strong></div>
            <div style="margin-bottom: 4px;">Giờ bắt đầu: <strong>{{ $newShift['gio_bat_dau'] }}</strong></div>
            <div>Giờ kết thúc: <strong>{{ $newShift['gio_ket_thuc'] }}</strong></div>
        </div>
    </div>

    <p>Vui lòng kiểm tra lại lịch làm việc của bạn để biết thêm chi tiết.</p>
    <p>Trân trọng,<br><strong>Đội ngũ quản lý XM COFFEE</strong></p>
</div>
