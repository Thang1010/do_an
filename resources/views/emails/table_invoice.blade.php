<div style="font-family: sans-serif; line-height: 1.6; color: #333;">
    <p>XM COFFEE — <strong>Hoá đơn thanh toán Bàn {{ $soBan }}</strong></p>
    <p>Cảm ơn quý khách. Dưới đây là hoá đơn của bàn:</p>

    <div style="margin-bottom: 12px; padding: 10px; background-color: #f9f9f9; border-radius: 8px;">
        @foreach($lines as $item)
            <div style="margin-bottom: 8px; padding-bottom: 8px; border-bottom: 1px dashed #ddd;">
                <div style="font-weight: 600;">
                    • {{ $item['ten'] }}
                    <span style="font-weight: 400; font-size: 12px; color: {{ $item['paid_now'] ? '#2563eb' : '#16a34a' }};">
                        [{{ $item['paid_now'] ? 'Thu lần này' : 'Đã thanh toán trước' }}]
                    </span>
                </div>
                @if(!empty($item['size']))
                    <div style="margin-left: 14px; color: #555;">- Kích cỡ: {{ $item['size'] }}</div>
                @endif
                <div style="margin-left: 14px; color: #555;">- Số lượng: {{ $item['sl'] }}</div>
                <div style="margin-left: 14px; color: #555;">- Thành tiền: {{ number_format($item['thanh_tien'], 0, ',', '.') }}đ</div>
                @if(!empty($item['ghi_chu']))
                    <div style="margin-left: 14px; color: #d97706; font-style: italic;">- Ghi chú: {{ $item['ghi_chu'] }}</div>
                @endif
            </div>
        @endforeach
    </div>

    <div style="margin-bottom: 4px;">Phương thức (lần thu này): {{ $method ?? 'chuyển khoản' }}</div>
    <div style="margin-bottom: 12px;">Thời gian: {{ $thoiGian }}</div>

    <div style="margin-bottom: 4px;">Tổng hoá đơn: <strong>{{ number_format($grandTotal, 0, ',', '.') }}đ</strong></div>
    @if($paidBefore > 0)
        <div style="margin-bottom: 4px; color: #16a34a;">Đã thanh toán trước: -{{ number_format($paidBefore, 0, ',', '.') }}đ</div>
    @endif
    <div style="margin-bottom: 4px;">Thu lần này: <strong>{{ number_format($payNow, 0, ',', '.') }}đ</strong></div>
    <div style="margin-bottom: 12px;">Còn lại: {{ number_format(max(0, $grandTotal - $paidBefore - $payNow), 0, ',', '.') }}đ</div>

    <p>Rất hân hạnh được phục vụ quý khách!</p>
    <p>Trân trọng,<br>Hệ thống XM COFFEE</p>
</div>
