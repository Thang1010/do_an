<div style="font-family: sans-serif; line-height: 1.6; color: #333; max-width: 520px;">
    <p>Xin chào <strong>{{ $user->ho_ten ?? 'bạn' }}</strong>,</p>
    <p>Ca làm việc dưới đây của bạn tại <strong>XM COFFEE</strong> đã bị hủy:</p>

    <div style="padding: 12px; background-color: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; margin-bottom: 16px;">
        <div style="font-weight: 700; color: #b91c1c; margin-bottom: 8px;">CA BỊ HỦY</div>
        <div style="margin-bottom: 4px;">Tên ca: <strong>{{ $shift['ten_ca'] }}</strong></div>
        <div style="margin-bottom: 4px;">Ngày làm: <strong>{{ \Carbon\Carbon::parse($shift['ngay_lam'])->format('d/m/Y') }}</strong></div>
        <div style="margin-bottom: 4px;">Giờ bắt đầu: <strong>{{ $shift['gio_bat_dau'] }}</strong></div>
        <div>Giờ kết thúc: <strong>{{ $shift['gio_ket_thuc'] }}</strong></div>
    </div>

    <p>Vui lòng liên hệ quản lý nếu có thắc mắc.</p>
    <p>Trân trọng,<br><strong>Đội ngũ quản lý XM COFFEE</strong></p>
</div>
