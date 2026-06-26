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

    // Các origin hợp lệ của trang thanh toán PayOS (lấy theo SDK @payos/payos-checkout).
    var PAYOS_ORIGINS = ['https://pay.payos.vn', 'https://dev.pay.payos.vn', 'https://next.dev.pay.payos.vn'];

    window.PayOSPayment = {
        _timer: null,
        _exited: false,
        _activeCfg: null,

        // Dừng việc poll (gọi khi đóng modal thanh toán).
        stop: function () {
            if (PayOSPayment._timer) { clearInterval(PayOSPayment._timer); PayOSPayment._timer = null; }
        },

        // Thêm ?iframe=true&redirect_uri=... để PayOS chạy ở chế độ nhúng: khi khách bấm Hủy/Thanh toán
        // nó gửi postMessage về trang cha NGAY LẬP TỨC (thay vì hiện trang "đã hủy" rồi mới redirect)
        // → đóng modal tức thì, không còn màn hình trắng/khoảng trống.
        // PayOS yêu cầu BẮT BUỘC có redirect_uri đi kèm iframe=true, thiếu sẽ báo "Thông tin truyền lên không hợp lệ".
        _withIframeFlag: function (url, returnUrl) {
            if (!url) return url;
            var sep = url.indexOf('?') === -1 ? '?' : '&';
            // redirect_uri PHẢI là returnUrl đã đăng ký với link, và phải encode (returnUrl có sẵn query).
            return url + sep + 'iframe=true&redirect_uri=' + encodeURIComponent(returnUrl || window.location.origin);
        },

        // Xử lý khi khách hủy thanh toán (gọi từ postMessage của PayOS, của trang hủy, hoặc khi poll thấy "đã hủy").
        _cancel: function () {
            if (PayOSPayment._exited) return;
            PayOSPayment._exited = true;
            PayOSPayment.stop();
            var cfg = PayOSPayment._activeCfg;
            if (cfg && cfg.iframe) { cfg.iframe.style.display = 'none'; }
            if (cfg && typeof cfg.onCancel === 'function') { cfg.onCancel(); }
            else { window.location.reload(); }
        },

        start: function (cfg) {
            cfg = cfg || {};
            PayOSPayment._exited = false;
            PayOSPayment._activeCfg = cfg;
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
                    if (cfg.iframe)      { cfg.iframe.src = PayOSPayment._withIframeFlag(data.checkoutUrl, data.returnUrl); cfg.iframe.style.display = 'block'; }
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
                        } else if (st && st.status === 'đã hủy') {
                            // Dự phòng: nếu vì lý do gì không nhận được postMessage, poll thấy "đã hủy" → đóng modal.
                            PayOSPayment._cancel();
                        }
                    })
                    .catch(function () { /* tiếp tục poll ở lần sau */ });
            }, cfg.interval || 2000);
        }
    };

    window.addEventListener('message', function (ev) {
        // 1) postMessage trực tiếp từ trang thanh toán PayOS (chế độ ?iframe=true).
        //    Khách bấm Hủy → nhận ngay {type:'payment_response', data:{status:'CANCELLED'}} → đóng modal tức thì.
        if (PAYOS_ORIGINS.indexOf(ev.origin) !== -1) {
            var msg;
            try { msg = typeof ev.data === 'string' ? JSON.parse(ev.data) : ev.data; } catch (e) { return; }
            if (!msg) return;
            var status = msg.data && msg.data.status;
            // type 'status'/'error' = khách đóng iframe; 'payment_response' + CANCELLED = bấm Hủy.
            if (msg.type === 'status' || msg.type === 'error' || status === 'CANCELLED') {
                PayOSPayment._cancel();
            }
            // PAID để việc poll xác nhận với server tự xử lý onPaid (đảm bảo đơn đã được ghi nhận).
            return;
        }

        // 2) Trang HỦY của PayOS (cart.payos.cancel) nạp trong iframe (đường dự phòng khi không có postMessage trực tiếp).
        if (ev.origin !== window.location.origin) return;
        var d = ev.data || {};
        if (!d || d.payosEvent !== 'cancel') return;
        PayOSPayment._cancel();
    });
})();
</script>
