@php
    $isNegative = $camXuc === 'Tiêu cực';
    $accent = $isNegative ? '#b91c1c' : '#b45309';
    $bg = $isNegative ? '#fef2f2' : '#fffbeb';
    $stars = (int) ($review->so_sao ?? 0);
    $khach = $review->nguoiDung?->hoSoKhachHang?->ho_ten
        ?: ($review->nguoiDung?->ho_ten ?: ($review->nguoiDung?->email ?? 'Khách hàng'));
    $sanPham = $review->sanPham?->ten_san_pham ?? 'Sản phẩm';
@endphp
<div style="font-family: sans-serif; line-height: 1.6; color: #333;">
    <p>Xin chào Quản lý/Chủ cửa hàng,</p>
    <p>Hệ thống vừa ghi nhận một đánh giá <strong style="color: {{ $accent }};">{{ $camXuc }}</strong> từ khách hàng. Vui lòng xem xét và cải thiện kịp thời.</p>

    <div style="margin-bottom: 16px; padding: 14px; background-color: {{ $bg }}; border-left: 4px solid {{ $accent }}; border-radius: 8px;">
        <div style="margin-bottom: 6px;">Sản phẩm: <strong>{{ $sanPham }}</strong></div>
        <div style="margin-bottom: 6px;">Cảm xúc (AI phân tích): <strong style="color: {{ $accent }};">{{ $camXuc }}</strong></div>
        <div style="margin-bottom: 6px;">Số sao: <strong>{{ str_repeat('★', $stars) }}{{ str_repeat('☆', max(0, 5 - $stars)) }}</strong> ({{ $stars }}/5)</div>
        <div style="margin-bottom: 6px;">Khách hàng: <strong>{{ $khach }}</strong></div>
        <div>Thời gian: <strong>{{ optional($review->created_at)->format('H:i d/m/Y') ?? now()->format('H:i d/m/Y') }}</strong></div>
    </div>

    <p style="margin-bottom: 4px;">Nội dung đánh giá:</p>
    <div style="margin-bottom: 16px; padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-style: italic; color: #475569;">
        “{{ $review->noi_dung ?: 'Khách hàng không để lại nội dung.' }}”
    </div>

    <p>
        <a href="{{ route('menu.show', $review->san_pham_id) }}"
           style="display: inline-block; padding: 10px 18px; background-color: {{ $accent }}; color: #fff; text-decoration: none; border-radius: 8px; font-weight: 600;">
            Xem sản phẩm &amp; các đánh giá
        </a>
    </p>

    <p style="margin-top: 16px;">Trân trọng,<br><strong>Hệ thống XM COFFEE</strong></p>
</div>
