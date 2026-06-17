<div style="font-family: sans-serif; line-height: 1.6; color: #333; max-width: 520px;">
    <p>Xin chào <strong>{{ $user->ho_ten ?? 'bạn' }}</strong>,</p>
    <p>
        Bạn đã <strong>quên chấm công ra</strong> sau khi kết thúc ca làm việc tại
        <strong>XM COFFEE</strong>. Hệ thống đã <strong>tự động chấm công ra</strong> giúp bạn.
    </p>

    <div style="padding: 14px 16px; background-color: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; margin-bottom: 16px;">
        <div style="margin-bottom: 4px;">Tên ca: <strong>{{ $shift->ten_ca }}</strong></div>
        <div style="margin-bottom: 4px;">Ngày làm: <strong>{{ \Carbon\Carbon::parse($shift->ngay_lam)->format('d/m/Y') }}</strong></div>
        <div style="margin-bottom: 4px;">Giờ bắt đầu: <strong>{{ \Carbon\Carbon::parse($shift->gio_bat_dau)->format('H:i') }}</strong></div>
        <div style="margin-bottom: 4px;">Giờ kết thúc: <strong>{{ \Carbon\Carbon::parse($shift->gio_ket_thuc)->format('H:i') }}</strong></div>
        <div style="margin-bottom: 4px;">Thời điểm chấm công ra: <strong>{{ $checkoutTime }}</strong></div>
        <div>Ghi chú: <strong>{{ $note }}</strong></div>
    </div>

    <p>
        Lưu ý: hệ thống ghi nhận giờ ra theo <strong>giờ kết thúc ca</strong>. Lần sau vui lòng
        chủ động chấm công ra để đảm bảo dữ liệu công chính xác.
    </p>
    <p>Trân trọng,<br><strong>Đội ngũ quản lý XM COFFEE</strong></p>
</div>
