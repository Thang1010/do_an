@extends('staff.layout.app')

@section('title', 'Quản lý bàn')
@section('breadcrumb')
Nhân viên / <strong>Bàn</strong>
@endsection

@section('content')

{{-- Staff Info Bar --}}
<div class="staff-info-bar">
    <div class="staff-info-left">
        <img class="staff-avatar" src="{{ auth()->user()->avatar_url }}" alt="Avatar">
        <div>
            <div class="staff-name">{{ auth()->user()->ho_ten ?? 'Nhân viên' }}</div>
            <div class="staff-role">{{ auth()->user()->vai_tro ?? 'nhân viên' }}</div>
        </div>
    </div>
    <div class="staff-info-right">
        <div class="staff-datetime" id="staff-live-clock">Đang tải...</div>
        @if($currentAttendance && !$currentAttendance->cham_cong_ra)
            <form method="POST" action="{{ route('staff.shifts.checkout') }}" style="display:inline;" class="js-geo-form"
                  data-confirm="Xác nhận chấm công ra?">
                @csrf
                <input type="hidden" name="attendance_id" value="{{ $currentAttendance->id }}">
                <input type="hidden" name="latitude">
                <input type="hidden" name="longitude">
                <button type="submit" class="shift-badge shift-badge--checkout">
                    Chấm công ra
                </button>
            </form>
        @elseif($currentShift && !$currentAttendance)
            <form method="POST" action="{{ route('staff.shifts.checkin') }}" style="display:inline;" class="js-geo-form">
                @csrf
                <input type="hidden" name="ca_lam_viec_id" value="{{ $currentShift->id }}">
                <input type="hidden" name="latitude">
                <input type="hidden" name="longitude">
                <button type="submit" class="shift-badge shift-badge--checkin">
                    Chấm công vào
                </button>
            </form>
        @elseif($currentAttendance && $currentAttendance->cham_cong_ra)
            <span class="shift-badge shift-badge--none">Đã hoàn thành ca</span>
        @else
            <span class="shift-badge shift-badge--none">Không có ca</span>
        @endif
    </div>
</div>

{{-- POS Layout: Left (Menu or Table Grid) | Right (Detail) --}}
<div class="pos-layout">
    <div class="pos-main" id="pos-left-panel">
        @include('staff.tables.partials.left-panel')
    </div>

    <div class="pos-detail detail-panel" id="pos-detail-panel">
        @include('staff.tables.partials.detail-panel')
    </div>
</div>

@include('staff.shifts.partials.geo-capture')

@endsection

@push('scripts')
<script>

function bindModalClose(modal) {
    var closes = modal.querySelectorAll('[data-modal-close]');
    closes.forEach(function(el) {
        el.addEventListener('click', function() {
            modal.classList.remove('modal--open');
        });
    });
}

var discountType = 'phần trăm';
var savedDiscountInputs = {
    'phần trăm': '',
    'tiền': ''
};

function getDiscountSubtotal() {
    var trigger = document.getElementById('discount-trigger');
    return trigger ? (parseFloat(trigger.dataset.subtotal || '0') || 0) : 0;
}

function formatVnd(n) {
    return Math.round(n).toLocaleString('vi-VN') + 'đ';
}

function setDiscountType(type) {
    discountType = type;
    var p = document.getElementById('discount-type-percent');
    var a = document.getElementById('discount-type-amount');
    var label = document.getElementById('discount-value-label');
    var input = document.getElementById('discount-value-input');
    if (p) p.classList.toggle('is-active', type === 'phần trăm');
    if (a) a.classList.toggle('is-active', type === 'tiền');
    if (label) label.textContent = type === 'phần trăm' ? 'Phần trăm giảm (%)' : 'Số tiền giảm (đ)';
    if (input) {
        input.max = type === 'phần trăm' ? '100' : String(getDiscountSubtotal());
        input.value = savedDiscountInputs[type] || '';
    }
    updateDiscountPreview();
}

function updateDiscountPreview() {
    var input = document.getElementById('discount-value-input');
    var hint = document.getElementById('discount-hint');
    var error = document.getElementById('discount-error');
    if (!input || !hint) return;
    
    var rawText = input.value;
    // Kiểm tra xem có chứa chữ cái hoặc ký tự đặc biệt không (ngoại trừ số và tối đa 1 dấu chấm)
    var hasLetters = /[^0-9.]/.test(rawText) || (rawText.match(/\./g) || []).length > 1;
    
    if (hasLetters && rawText !== '') {
        input.style.borderColor = '#d92d20';
        input.style.backgroundColor = '#fef2f2';
        if (error) error.style.display = 'block';
        hint.textContent = '—';
        return; // Dừng lại, không cập nhật preview hay lưu session
    } else {
        input.style.borderColor = '';
        input.style.backgroundColor = '';
        if (error) error.style.display = 'none';
    }

    var subtotal = getDiscountSubtotal();
    var val = parseFloat(rawText || '0');
    
    // Ràng buộc giá trị hiển thị không vượt quá 100%
    if (discountType === 'phần trăm' && val > 100) {
        val = 100;
        input.value = 100;
    }
    
    // Lưu lại giá trị người dùng vừa nhập để khi chuyển tab không bị mất
    savedDiscountInputs[discountType] = input.value;
    
    if (isNaN(val) || val <= 0) {
        hint.textContent = 'Tạm tính: ' + formatVnd(subtotal);
        return;
    }
    var discount = discountType === 'phần trăm'
        ? subtotal * val / 100
        : Math.min(val, subtotal);
    hint.textContent = 'Giảm ' + formatVnd(discount) + ' → còn ' + formatVnd(Math.max(0, subtotal - discount));
}

function openDiscountModal() {
    var modal = document.getElementById('discount-modal');
    if (!modal) return;
    var trigger = document.getElementById('discount-trigger');
    
    var savedType = trigger ? (trigger.dataset.type || 'phần trăm') : 'phần trăm';
    var savedVal = trigger ? (parseFloat(trigger.dataset.value || '0') || 0) : 0;
    
    // Reset state mỗi lần mở lại modal
    savedDiscountInputs = {
        'phần trăm': '',
        'tiền': ''
    };
    if (savedVal > 0) {
        savedDiscountInputs[savedType] = savedVal;
    }
    
    setDiscountType(savedType);
    
    modal.classList.add('modal--open');
}

function closeDiscountModal() {
    var modal = document.getElementById('discount-modal');
    if (modal) modal.classList.remove('modal--open');
}

function submitDiscount(loai, giaTri) {
    var form = document.getElementById('order-update-form');
    if (!form) return;
    var loaiInput = document.getElementById('chiet-khau-loai');
    var giaTriInput = document.getElementById('chiet-khau-gia-tri');
    if (loaiInput) loaiInput.value = loai;
    if (giaTriInput) giaTriInput.value = giaTri;
    var draftButton = form.querySelector('button[name="action"][value="draft"]');
    if (form.requestSubmit && draftButton) {
        form.requestSubmit(draftButton);
    } else if (draftButton) {
        draftButton.click();
    } else {
        form.submit();
    }
}

function applyDiscount() {
    var input = document.getElementById('discount-value-input');
    var rawText = input ? input.value : '';
    
    // Nếu đang có lỗi nhập chữ, hiển thị cảnh báo và không cho áp dụng
    var hasLetters = /[^0-9.]/.test(rawText) || (rawText.match(/\./g) || []).length > 1;
    if (hasLetters && rawText !== '') {
        updateDiscountPreview(); // Gọi lại để kích hoạt UI lỗi
        return;
    }
    
    var val = parseFloat(rawText || '0');
    if (isNaN(val) || val <= 0) {
        closeDiscountModal();
        return;
    }
    if (discountType === 'phần trăm') {
        val = Math.min(val, 100);
    }
    submitDiscount(discountType, val);
}

function clearDiscount() {
    submitDiscount('', 0);
}

function initPaymentModal() {
    var modal = document.getElementById('payment-modal');
    if (!modal || modal.dataset.bound === '1') return;
    modal.dataset.bound = '1';

    // Bind close handlers; also clear countdown when modal is closed
    var closes = modal.querySelectorAll('[data-modal-close]');
    closes.forEach(function(el) {
        el.addEventListener('click', function() {
            modal.classList.remove('modal--open');
            if (typeof clearQrCountdown === 'function') clearQrCountdown();
        });
    });

    var methodButtons = modal.querySelectorAll('[data-payment-method]');
    var methodInput = document.getElementById('payment-method-input');
    var qrBox = document.getElementById('payment-modal-qr');
    var qrImage = document.getElementById('payment-qr-image');
    var qrNote = document.getElementById('payment-qr-note');
    var bankName = document.getElementById('payment-bank-name');
    var bankAccount = document.getElementById('payment-bank-account');
    var bankOwner = document.getElementById('payment-bank-owner');
    function setMethod(method) {
        if (!method) return;
        if (methodInput) methodInput.value = method;
        methodButtons.forEach(function(btn) {
            btn.classList.toggle('is-active', btn.dataset.paymentMethod === method);
        });
        var submitBtn = document.getElementById('payment-modal-submit');
        if (method === 'chuyển khoản') {
            if (qrBox) qrBox.style.display = 'block';
            if (submitBtn) submitBtn.style.display = 'none';
        } else {
            if (qrBox) qrBox.style.display = 'none';
            if (submitBtn) submitBtn.style.display = 'inline-flex';
        }
    }

    methodButtons.forEach(function(btn) {
        btn.addEventListener('click', function() {
            setMethod(btn.dataset.paymentMethod);
        });
    });

    setMethod(methodInput ? methodInput.value : 'chuyển khoản');

    if (modal.dataset.autoOpen === '1') {
        modal.classList.add('modal--open');
        setMethod(methodInput ? methodInput.value : 'chuyển khoản');
        modal.dataset.autoOpen = '0';
        try {
            var url = new URL(window.location.href);
            url.searchParams.delete('payment');
            window.history.replaceState({}, '', url.toString());
        } catch (e) {
            // ignore history errors
        }
    }
}

function startPosAutoRefresh() {
    var leftPanel = document.getElementById('pos-left-panel');
    var detailPanel = document.getElementById('pos-detail-panel');
    if (!leftPanel || !detailPanel) return;

    var intervalMs = 10000;
    var isRefreshing = false;

    async function refreshPanels() {
        if (isRefreshing || document.hidden) return;
        if (document.querySelector('.modal.modal--open')) return;
        var activeEl = document.activeElement;
        if (activeEl && ['INPUT', 'TEXTAREA', 'SELECT'].includes(activeEl.tagName)) return;

        isRefreshing = true;
        try {
            var url = new URL(window.location.href);
            url.searchParams.set('partial', '1');
            var res = await fetch(url.toString(), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!res.ok) return;
            var data = await res.json();
            if (data.left) leftPanel.innerHTML = data.left;
            if (data.detail) detailPanel.innerHTML = data.detail;

            initPaymentModal();
        } catch (e) {
            // Ignore refresh errors to avoid blocking UI
        } finally {
            isRefreshing = false;
        }
    }

    setInterval(refreshPanels, intervalMs);
}

document.addEventListener('DOMContentLoaded', function() {
    initPaymentModal();
    startPosAutoRefresh();

    // ── Ajax Chuyển danh mục không reload trang ──
    document.addEventListener('click', async function(e) {
        var categoryLink = e.target.closest('.menu-category-item');
        if (categoryLink) {
            e.preventDefault();
            var href = categoryLink.getAttribute('href');
            if (!href) return;

            // Đổi class active lập tức để tạo cảm giác mượt mà (snappy UI)
            document.querySelectorAll('.menu-category-item').forEach(function(el) {
                el.classList.remove('active');
            });
            categoryLink.classList.add('active');

            try {
                var url = new URL(href, window.location.origin);
                url.searchParams.set('partial', '1');

                var res = await fetch(url.toString(), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!res.ok) {
                    window.location.href = href;
                    return;
                }
                var data = await res.json();

                var leftPanel = document.getElementById('pos-left-panel');
                var detailPanel = document.getElementById('pos-detail-panel');
                if (leftPanel && data.left) {
                    leftPanel.innerHTML = data.left;
                }
                if (detailPanel && data.detail) {
                    detailPanel.innerHTML = data.detail;
                    initPaymentModal();
                }

                window.history.pushState({}, '', href);
            } catch (err) {
                console.error('Error switching category:', err);
                window.location.href = href;
            }
        }
    });

    // Hỗ trợ nút Back/Forward của trình duyệt
    window.addEventListener('popstate', async function() {
        var leftPanel = document.getElementById('pos-left-panel');
        var detailPanel = document.getElementById('pos-detail-panel');
        if (!leftPanel || !detailPanel) return;

        try {
            var url = new URL(window.location.href);
            url.searchParams.set('partial', '1');
            var res = await fetch(url.toString(), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!res.ok) return;
            var data = await res.json();
            if (data.left) leftPanel.innerHTML = data.left;
            if (data.detail) detailPanel.innerHTML = data.detail;
            initPaymentModal();
        } catch (e) {
            // im lặng
        }
    });
});
</script>
@endpush
