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
        @if($currentAttendance && !$currentAttendance->check_out_luc)
            <form method="POST" action="{{ route('staff.shifts.checkout') }}" style="display:inline;">
                @csrf
                <input type="hidden" name="attendance_id" value="{{ $currentAttendance->id }}">
                <button type="submit" class="shift-badge shift-badge--checkout"
                        onclick="return confirm('Xác nhận chấm công ra?')">
                    Chấm công ra
                </button>
            </form>
        @elseif($currentShift && !$currentAttendance)
            <form method="POST" action="{{ route('staff.shifts.checkin') }}" style="display:inline;">
                @csrf
                <input type="hidden" name="ca_lam_viec_id" value="{{ $currentShift->id }}">
                <button type="submit" class="shift-badge shift-badge--checkin">
                    Chấm công vào
                </button>
            </form>
        @elseif($currentAttendance && $currentAttendance->check_out_luc)
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

function initVoucherDetails() {
    var modal = document.getElementById('voucher-modal');
    var trigger = document.getElementById('voucher-detail-trigger');
    var select = document.getElementById('voucher-select');
    var form = document.getElementById('order-update-form');
    var autoFlag = document.getElementById('auto-voucher-flag');
    var draftButton = form ? form.querySelector('button[name="action"][value="draft"]') : null;
    if (!modal || !trigger || !select || modal.dataset.bound === '1') return;
    modal.dataset.bound = '1';

    bindModalClose(modal);

    function populate() {
        var opt = select.options[select.selectedIndex];
        if (!opt || !opt.dataset.code) return;
        document.getElementById('voucher-modal-title').textContent = opt.dataset.name || '—';
        document.getElementById('voucher-modal-code').textContent = opt.dataset.code || '—';
        var discount = opt.dataset.discount || '—';
        var type = opt.dataset.discountType || '';
        document.getElementById('voucher-modal-discount').textContent = type ? (discount + ' (' + type + ')') : discount;
        document.getElementById('voucher-modal-min').textContent = opt.dataset.min || '—';
    }

    trigger.addEventListener('click', function() {
        if (select.disabled) return;
        var opt = select.options[select.selectedIndex];
        if (!opt || !opt.dataset.code) return;
        populate();
        modal.classList.add('modal--open');
    });

    select.addEventListener('change', function() {
        if (!form || !draftButton) return;
        if (autoFlag) autoFlag.value = '1';
        if (form.requestSubmit) {
            form.requestSubmit(draftButton);
        } else {
            draftButton.click();
        }
    });
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
    
            initVoucherDetails();
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
    initVoucherDetails();
    initPaymentModal();
    startPosAutoRefresh();
});
</script>
@endpush
