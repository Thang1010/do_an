@extends('staff.layout.app')

@section('title', 'Quản lý bàn')
@section('breadcrumb', 'Quản lý / <strong>Bàn</strong>')

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
                        onclick="return confirm('Xác nhận check-out ca làm việc?')">
                    Check-out Ca làm việc
                </button>
            </form>
        @elseif($currentShift)
            <form method="POST" action="{{ route('staff.shifts.checkin') }}" style="display:inline;">
                @csrf
                <input type="hidden" name="ca_lam_viec_id" value="{{ $currentShift->id }}">
                <button type="submit" class="shift-badge shift-badge--checkin">
                    Check-in Ca làm việc
                </button>
            </form>
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
function initQrPayment() {
    var btn = document.getElementById('generate-qr-btn');
    if (!btn || btn.dataset.bound === '1') return;
    btn.dataset.bound = '1';
    var panel = document.getElementById('qr-panel');
    var image = document.getElementById('qr-image');
    var hint = document.getElementById('qr-hint');
    var countdown = document.getElementById('qr-countdown');
    var timer = document.getElementById('qr-timer');
    var message = document.getElementById('qr-message');
    var interval = null;

    btn.addEventListener('click', async function() {
        btn.disabled = true;
        message.textContent = 'Đang tạo mã QR...';
        message.style.color = '';
        try {
            var res = await fetch(btn.dataset.url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            var data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Lỗi tạo QR');
            image.src = data.qr_url;
            panel.style.display = '';
            countdown.style.display = '';
            message.textContent = data.message || '';
            var remain = data.expires_in || 60;
            timer.textContent = remain;
            if (interval) clearInterval(interval);
            interval = setInterval(function() {
                remain--;
                timer.textContent = Math.max(remain, 0);
                if (remain <= 0) {
                    clearInterval(interval);
                    panel.style.display = 'none';
                    message.textContent = 'QR đã hết hạn. Vui lòng tạo lại.';
                    message.style.color = '#b42318';
                }
            }, 1000);
        } catch(e) {
            panel.style.display = 'none';
            message.textContent = e.message;
            message.style.color = '#b42318';
        }
        btn.disabled = false;
    });
}


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

    bindModalClose(modal);

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
        if (method === 'chuyển khoản') {
            qrBox.style.display = '';
            loadQr();
        } else {
            qrBox.style.display = 'none';
        }
    }

    async function loadQr() {
        if (!modal.dataset.qrUrl || modal.dataset.qrLoading === '1') return;
        modal.dataset.qrLoading = '1';
        if (qrNote) qrNote.textContent = 'Đang tạo mã QR...';
        try {
            var res = await fetch(modal.dataset.qrUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            var data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Không thể tạo mã QR.');
            if (qrImage) qrImage.src = data.qr_url;
            if (qrNote) qrNote.textContent = data.message || '';
            if (bankName) bankName.textContent = data.bank_name || '—';
            if (bankAccount) bankAccount.textContent = data.account_no || '—';
            if (bankOwner) bankOwner.textContent = data.account_name || '—';
        } catch (e) {
            if (qrNote) {
                qrNote.textContent = e.message;
            }
        } finally {
            modal.dataset.qrLoading = '0';
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
            initQrPayment();
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
    initQrPayment();
    initVoucherDetails();
    initPaymentModal();
    startPosAutoRefresh();
});
</script>
@endpush
