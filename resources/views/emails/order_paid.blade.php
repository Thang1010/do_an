<div style="font-family: sans-serif; line-height: 1.6; color: #333;">
    <p>Xin chào Quản lý/Chủ cửa hàng,</p>
    <p>Đơn hàng <strong>{{ $order->ma_don_hang ?? ('DON' . $order->id) }}</strong> vừa được thanh toán thành công.</p>
    
    <div style="margin-bottom: 12px; padding: 10px; background-color: #f1f5f9; border-radius: 8px;">
        <div style="margin-bottom: 4px;">Tổng tiền: <strong>{{ number_format($order->tong_tien ?? 0, 0, ',', '.') }}đ</strong></div>
        <div style="margin-bottom: 4px;">Phương thức: <strong>{{ $order->phuong_thuc_thanh_toan ?? 'chuyển khoản' }}</strong></div>
        <div style="margin-bottom: 4px;">Thời gian thanh toán: <strong>{{ now()->format('H:i d/m/Y') }}</strong></div>
        <div style="margin-bottom: 4px;">Thời gian đặt hàng: <strong>{{ optional($order->created_at)->format('H:i d/m/Y') }}</strong></div>
        <div style="margin-bottom: 4px;">{{ $order->loai_don === 'đặt hàng trước' ? 'Thời gian hẹn đến' : 'Dự kiến hoàn thành' }}: <strong>{{ $order->thoi_gian_den ? $order->thoi_gian_den->format('H:i d/m/Y') : '—' }}</strong></div>
    </div>
    
    <p style="margin-bottom: 4px;">Chi tiết món ăn:</p>
    <div style="margin-bottom: 16px; padding: 10px; border: 1px solid #e2e8f0; border-radius: 8px;">
        @foreach($order->chiTietDonHang as $item)
            <div style="margin-bottom: 8px; padding-bottom: 8px; border-bottom: 1px dashed #cbd5e1;">
                <div style="font-weight: 600; color: #0f172a;">• {{ $item->ten_san_pham }}</div>
                <div style="margin-left: 14px; color: #475569;">- Kích cỡ: {{ $item->ten_kich_co ?? 'Mặc định' }}</div>
                <div style="margin-left: 14px; color: #475569;">- Số lượng: {{ $item->so_luong }}</div>
                <div style="margin-left: 14px; color: #475569;">- Thành tiền: {{ number_format($item->thanh_tien, 0, ',', '.') }}đ</div>
                @if($item->ghi_chu_mon)
                    <div style="margin-left: 14px; color: #b45309; font-style: italic;">- Ghi chú: {{ $item->ghi_chu_mon }}</div>
                @endif
            </div>
        @endforeach
    </div>
    
    <p>Vui lòng kiểm tra lại hệ thống để biết thêm chi tiết.</p>
    <p>Trân trọng,<br><strong>Hệ thống XM COFFEE</strong></p>
</div>
