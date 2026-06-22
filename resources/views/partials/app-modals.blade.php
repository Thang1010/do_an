{{--
    Modal dùng chung cho toàn hệ thống — thay cho alert()/confirm() mặc định của trình duyệt.
    Luôn căn giữa màn hình, dùng chung 1 style.

    API (global):
        showNotice(message, opts?)                 // hộp thông báo (1 nút OK). opts: { title }
        showConfirm(message, onConfirm, opts?)     // hộp xác nhận (Hủy / Xác nhận). opts: { title, okText }
        confirmSubmit(form, message, opts?)        // dùng cho onsubmit="return confirmSubmit(this, '...')"
--}}
@once
<div id="app-modal" class="app-modal" role="dialog" aria-modal="true" aria-hidden="true">
    <div class="app-modal__backdrop" data-app-modal-cancel></div>
    <div class="app-modal__panel">
        <div class="app-modal__title" id="app-modal-title">Thông báo</div>
        <div class="app-modal__message" id="app-modal-message"></div>
        <div class="app-modal__actions">
            <button type="button" class="app-modal__btn app-modal__btn--secondary" id="app-modal-cancel" data-app-modal-cancel>Hủy</button>
            <button type="button" class="app-modal__btn app-modal__btn--primary" id="app-modal-ok">Đồng ý</button>
        </div>
    </div>
</div>

<style>
    .app-modal {
        position: fixed;
        inset: 0;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 20px;
        z-index: 100000;
        font-family: 'Outfit', sans-serif;
    }
    .app-modal.is-open { display: flex; }
    .app-modal__backdrop {
        position: absolute;
        inset: 0;
        background: rgba(18, 12, 8, 0.72);
        backdrop-filter: blur(2px);
    }
    .app-modal__panel {
        position: relative;
        width: min(420px, 92vw);
        background: rgba(30, 17, 6, 0.95);
        border: 1px solid rgba(240, 221, 184, 0.16);
        backdrop-filter: blur(14px);
        border-radius: 18px;
        padding: 26px 26px 22px;
        box-shadow: 0 24px 60px rgba(0, 0, 0, 0.45);
        color: #f1f0ee;
        text-align: center;
        animation: app-modal-pop 0.16s ease-out;
    }
    @keyframes app-modal-pop {
        from { transform: scale(0.96); opacity: 0; }
        to   { transform: scale(1); opacity: 1; }
    }
    .app-modal__title {
        font-family: 'Playfair Display', serif;
        font-size: 20px;
        font-weight: 700;
        color: #F0DDB8;
        margin-bottom: 10px;
    }
    .app-modal__message {
        font-size: 14px;
        color: rgba(241, 240, 238, 0.82);
        line-height: 1.6;
        margin-bottom: 22px;
        white-space: pre-line;
    }
    .app-modal__actions {
        display: flex;
        gap: 10px;
        justify-content: center;
    }
    .app-modal__btn {
        padding: 10px 22px;
        border-radius: 999px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        font-family: 'Outfit', sans-serif;
        transition: background 0.2s, opacity 0.2s;
    }
    .app-modal__btn--primary {
        border: none;
        background: #c49a6c;
        color: #1a120c;
    }
    .app-modal__btn--primary:hover { background: #d4aa7a; }
    .app-modal__btn--secondary {
        border: 1px solid rgba(240, 221, 184, 0.35);
        background: transparent;
        color: #F0DDB8;
    }
    .app-modal__btn--secondary:hover { background: rgba(255, 255, 255, 0.06); }
</style>

<script>
    (function () {
        if (window.__appModalReady) return;
        window.__appModalReady = true;

        var modal   = document.getElementById('app-modal');
        var titleEl = document.getElementById('app-modal-title');
        var msgEl   = document.getElementById('app-modal-message');
        var okBtn   = document.getElementById('app-modal-ok');
        var cancelBtn = document.getElementById('app-modal-cancel');
        var onOk = null;

        function close() {
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
            onOk = null;
        }

        function open(opts) {
            titleEl.textContent = opts.title || 'Thông báo';
            msgEl.textContent = opts.message || '';
            // chế độ confirm: hiện nút Hủy + đổi nhãn nút chính
            if (opts.confirm) {
                cancelBtn.style.display = '';
                okBtn.textContent = opts.okText || 'Xác nhận';
            } else {
                cancelBtn.style.display = 'none';
                okBtn.textContent = opts.okText || 'Đồng ý';
            }
            onOk = typeof opts.onOk === 'function' ? opts.onOk : null;
            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
            okBtn.focus();
        }

        okBtn.addEventListener('click', function () {
            var cb = onOk;
            close();
            if (cb) cb();
        });

        // Nút Hủy / nền: chỉ đóng (không chạy callback)
        modal.querySelectorAll('[data-app-modal-cancel]').forEach(function (el) {
            el.addEventListener('click', close);
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && modal.classList.contains('is-open')) close();
        });

        window.showNotice = function (message, opts) {
            opts = opts || {};
            open({ message: message, title: opts.title, okText: opts.okText, confirm: false });
        };

        window.showConfirm = function (message, onConfirm, opts) {
            opts = opts || {};
            open({
                message: message,
                title: opts.title || 'Xác nhận',
                okText: opts.okText || 'Xác nhận',
                confirm: true,
                onOk: onConfirm,
            });
        };

        // Dùng trong onsubmit="return confirmSubmit(this, '...')" — chặn submit, mở modal,
        // chỉ submit khi người dùng bấm Xác nhận.
        window.confirmSubmit = function (form, message, opts) {
            opts = opts || {};
            open({
                message: message,
                title: opts.title || 'Xác nhận',
                okText: opts.okText || 'Xác nhận',
                confirm: true,
                onOk: function () { form.submit(); },
            });
            return false;
        };
    })();
</script>
@endonce
