{{--
    Helper thanh toán PayOS dùng chung cho mọi màn hình (customer / manager / staff).

    Cách dùng (gọi từ view sau khi @include partial này):
        PayOSPayment.start({
            orderCode:   'DON123',                 // bắt buộc — mã đơn hàng
            source:      'manager',                // 'customer' | 'manager' | 'staff'
            email:       'a@b.com',                // tuỳ chọn — email nhận hoá đơn
            button:      el,                       // tuỳ chọn — nút bấm (sẽ disable/ẩn)
            iframe:      el,                       // tuỳ chọn — iframe hiển thị QR
            container:   el,                       // tuỳ chọn — khung bao QR
            loadingText: el,                       // tuỳ chọn — chữ "đang tải"
            successText: el,                       // tuỳ chọn — chữ "đã thanh toán"
            interval:    2000,                     // tuỳ chọn — chu kỳ poll (ms), mặc định 2000
            successDelay:600,                      // tuỳ chọn — chờ trước khi gọi onPaid (ms)
            onPaid:      function () { ... },      // BẮT BUỘC — chạy khi đơn đã thanh toán
            onFail:      function (msg) { ... },   // tuỳ chọn — tự xử lý khi lỗi tạo QR
        });

    Mọi luồng đều tự cập nhật trạng thái đơn nhờ /cart/payment/{code}/status?payosOrderId=...
--}}
<script>
(function () {
    if (window.PayOSPayment) return;

    window.PayOSPayment = {
        _timer: null,

        // Dừng việc poll (gọi khi đóng modal thanh toán).
        stop: function () {
            if (PayOSPayment._timer) { clearInterval(PayOSPayment._timer); PayOSPayment._timer = null; }
        },

        start: function (cfg) {
            cfg = cfg || {};
            var btn = cfg.button || null;
            var origLabel = btn ? btn.innerText : '';
            var emailParam = cfg.email ? ('&email=' + encodeURIComponent(cfg.email)) : '';

            var fail = function (msg) {
                if (typeof cfg.onFail === 'function') { cfg.onFail(msg); return; }
                var failMsg = msg || 'Có lỗi xảy ra. Vui lòng thử lại.';
                if (typeof window.showNotice === 'function') { window.showNotice(failMsg); } else { alert(failMsg); }
                if (btn) { btn.disabled = false; btn.innerText = origLabel || 'Tạo QR thanh toán PayOS'; }
            };

            if (btn) { btn.disabled = true; btn.innerText = 'Đang tạo...'; }

            fetch('/cart/payment/' + encodeURIComponent(cfg.orderCode) + '?source=' + (cfg.source || '') + emailParam, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data && data.success && data.checkoutUrl) {
                    if (cfg.iframe)      { cfg.iframe.src = data.checkoutUrl; cfg.iframe.style.display = 'block'; }
                    if (cfg.container)   { cfg.container.style.display = 'block'; }
                    if (cfg.loadingText) { cfg.loadingText.style.display = 'none'; }
                    if (btn)             { btn.style.display = 'none'; }
                    PayOSPayment._poll(cfg, (data.payosOrderId != null ? data.payosOrderId : null));
                } else {
                    fail(data && data.message ? data.message : 'Lỗi khi tạo mã thanh toán PayOS');
                }
            })
            .catch(function () { fail('Lỗi kết nối. Vui lòng thử lại.'); });
        },

        _poll: function (cfg, payosOrderId) {
            if (PayOSPayment._timer) clearInterval(PayOSPayment._timer);
            PayOSPayment._timer = setInterval(function () {
                var url = '/cart/payment/' + encodeURIComponent(cfg.orderCode) + '/status';
                if (payosOrderId) { url += '?payosOrderId=' + encodeURIComponent(payosOrderId); }
                fetch(url)
                    .then(function (r) { return r.json(); })
                    .then(function (st) {
                        if (st && st.success && st.status === 'đã thanh toán') {
                            clearInterval(PayOSPayment._timer);
                            if (cfg.iframe)      { cfg.iframe.style.display = 'none'; }
                            if (cfg.successText) { cfg.successText.style.display = 'block'; }
                            if (typeof cfg.onPaid === 'function') {
                                setTimeout(cfg.onPaid, (cfg.successDelay != null ? cfg.successDelay : 600));
                            }
                        }
                    })
                    .catch(function () { /* tiếp tục poll ở lần sau */ });
            }, cfg.interval || 2000);
        }
    };
})();
</script>
