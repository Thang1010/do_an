<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Đã hủy thanh toán</title>
    {{-- Chạy NGAY khi parse <head>, trước khi body kịp vẽ ra màn hình → báo trang
         cha đóng modal tức thì, không còn khoảng trống/nháy trắng giữa lúc hủy và đóng. --}}
    <script>
        (function () {
            var fallback = @json($fallbackUrl);
            try {
                // Đang nằm trong iframe thanh toán (modal) → báo trang cha đóng modal.
                if (window.parent && window.parent !== window) {
                    window.parent.postMessage({ payosEvent: 'cancel', orderCode: @json($orderCode) }, window.location.origin);
                    return;
                }
            } catch (e) { /* fallback dưới */ }
            // Mở full-page (không phải iframe) → điều hướng như cũ.
            window.location.replace(fallback);
        })();
    </script>
</head>
{{-- Nền tối trùng với modal: nếu iframe có kịp vẽ 1 frame thì cũng hoà vào modal, không loé trắng. --}}
<body style="background:#1a120c; margin:0;"></body>
</html>
