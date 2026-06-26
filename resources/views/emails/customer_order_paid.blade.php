<div style="font-family: sans-serif; line-height: 1.6; color: #333;">
    <p>Xin chào {{ $customerName }}, Cảm ơn bạn đã đặt hàng tại XM COFFEE!</p>
    <p>Đơn hàng <strong>{{ $order->ma_don_hang ?? ('DON' . $order->id) }}</strong> của bạn đã được thanh toán thành công.</p>
    
    <p style="margin-bottom: 4px;">Chi tiết món ăn:</p>
    <div style="margin-bottom: 12px; padding: 10px; background-color: #f9f9f9; border-radius: 8px;">
        @foreach($order->chiTietDonHang as $item)
            <div style="margin-bottom: 8px; padding-bottom: 8px; border-bottom: 1px dashed #ddd;">
                <div style="font-weight: 600;">• {{ $item->ten_san_pham }}</div>
                @if($item->ten_kich_co)
                    <div style="margin-left: 14px; color: #555;">- Kích cỡ: {{ $item->ten_kich_co }}</div>
                @endif
                <div style="margin-left: 14px; color: #555;">- Số lượng: {{ $item->so_luong }}</div>
                <div style="margin-left: 14px; color: #555;">- Thành tiền: {{ number_format($item->thanh_tien, 0, ',', '.') }}đ</div>
                @if($item->ghi_chu_mon)
                    <div style="margin-left: 14px; color: #d97706; font-style: italic;">- Ghi chú: {{ $item->ghi_chu_mon }}</div>
                @endif
            </div>
        @endforeach
    </div>

    <div style="margin-bottom: 4px;">Phương thức: {{ $order->phuong_thuc_thanh_toan ?? 'chuyển khoản' }}</div>
    <div style="margin-bottom: 4px;">Thời gian thanh toán: {{ now()->format('H:i d/m/Y') }}</div>
    <div style="margin-bottom: 4px;">Thời gian đặt hàng: {{ optional($order->created_at)->format('H:i d/m/Y') }}</div>

    @if($order->loai_don === 'đặt hàng trước')
        <div style="margin-bottom: 4px;">Loại đơn: đặt trước</div>
        <div style="margin-bottom: 4px;">Thời gian dự kiến đến: {{ $order->thoi_gian_den ? $order->thoi_gian_den->format('H:i d/m/Y') : '' }}</div>
        <div style="margin-bottom: 4px;">Bàn: {{ $order->banAn ? $order->banAn->so_ban : 'Chưa chọn' }}</div>
    @else
        <div style="margin-bottom: 4px;">Loại đơn: {{ $order->loai_don === 'mang về' ? 'mang về' : 'sử dụng tại quán' }}</div>
        <div style="margin-bottom: 4px;">Thời gian dự kiến hoàn thành: {{ $order->thoi_gian_den ? $order->thoi_gian_den->format('H:i d/m/Y') : '' }}</div>
        @if($order->loai_don !== 'mang về')
            <div style="margin-bottom: 4px;">Bàn: {{ $order->banAn ? $order->banAn->so_ban : 'Chưa chọn' }}</div>
        @endif
    @endif

    @if($order->voucherNguoiDung && $order->voucherNguoiDung->voucher)
        @php
            $v = $order->voucherNguoiDung->voucher;
            $voucherText = $v->loai_giam === 'phần trăm' ? $v->gia_tri_giam . '%' : number_format($v->gia_tri_giam, 0, ',', '.') . 'đ';
        @endphp
        <div style="margin-bottom: 4px;">Voucher áp dụng: <strong>{{ $v->ma_voucher }}</strong> (Giảm {{ $voucherText }})</div>
    @endif

    @if(($order->so_tien_giam ?? 0) > 0)
        <div style="margin-bottom: 4px; margin-top: 8px;">Tạm tính: {{ number_format($order->tam_tinh ?? 0, 0, ',', '.') }}đ</div>
        <div style="margin-bottom: 4px; color: #16a34a;">Chiết khấu: -{{ number_format($order->so_tien_giam, 0, ',', '.') }}đ</div>
    @endif

    <div style="margin-bottom: 12px; margin-top: 8px;">Tổng tiền: <strong>{{ number_format($order->tong_tien ?? 0, 0, ',', '.') }}đ</strong></div>
    
    <p>Món ngon của bạn đang được chuẩn bị. Rất hân hạnh được phục vụ bạn!</p>
    
    <p>Trân trọng,<br>Hệ thống XM COFFEE</p>
</div>
