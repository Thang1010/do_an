@extends('manager.layout.app')

@section('title', 'Sửa đơn hàng')
@section('breadcrumb')
Kinh doanh / <a href="{{ route('manager.orders.index') }}">Quản lý đơn hàng</a> / <strong>Sửa đơn</strong>
@endsection

@section('content')
@php
    $formItems = old('items');
    if (!is_array($formItems)) {
        $formItems = ($order->chiTietDonHang ?? collect())->map(function ($item) {
            return [
                'san_pham_id' => $item->san_pham_id,
                'kich_co_id' => $item->kich_co_id,
                'so_luong' => $item->so_luong,
                'ghi_chu_mon' => $item->ghi_chu_mon,
            ];
        })->toArray();
    }

    if (empty($formItems)) {
        $formItems = [[
            'san_pham_id' => '',
            'kich_co_id' => '',
            'so_luong' => 1,
            'ghi_chu_mon' => '',
        ]];
    }

    $paymentStatus = $order->trang_thai_thanh_toan ?? '—';
    $paymentStatusClass = match ($paymentStatus) {
        'đã thanh toán' => 'badge-success',
        'chưa thanh toán' => 'badge-warning',
        'thất bại' => 'badge-danger',
        default => 'badge-default',
    };
@endphp

<div class="page-header">
    <div>
        <h1 class="page-title">Sửa đơn hàng #{{ $order->id }}</h1>
        <p class="page-subtitle">Mã đơn: {{ $order->ma_don_hang }} • {{ optional($order->created_at)->format('d/m/Y H:i') ?? '—' }}</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('manager.orders.show', $order->id) }}" class="btn btn-secondary">Quay lại</a>
    </div>
</div>

<form method="POST" action="{{ route('manager.orders.update', $order->id) }}" id="edit-order-form">
    @csrf
    @method('PUT')

    <div class="card mb-20">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; gap: 8px;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <span class="card-title">Thông tin đơn hàng</span>
                <span style="background-color: #ffc107; color: #212529; font-size: 14px; padding: 4px 12px; border-radius: 6px; font-weight: 600; text-transform: uppercase; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">{{ $paymentStatus }}</span>
            </div>
            @if(($order->trang_thai_thanh_toan ?? 'chưa thanh toán') !== 'đã thanh toán' && auth()->check() && in_array(auth()->user()->vai_tro ?? '', ['quản lý', 'nhân viên', 'chủ cửa hàng'], true))
                <button type="button" class="btn btn-primary btn-sm" onclick="openPaymentModal()">Thanh toán</button>
            @endif
        </div>
        <div class="card-body">
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label">Loại đơn</label>
                    <input type="text" id="edit-order-type" class="form-control" value="{{ $order->loai_don }}" readonly>
                </div>
                <div class="form-group" id="edit-order-table-group">
                    <label class="form-label">Bàn ăn</label>
                    <select name="ban_an_id" id="edit-order-table" class="form-control @error('ban_an_id') is-invalid @enderror">
                        <option value="">Chọn bàn</option>
                        @foreach($banAns ?? [] as $table)
                            <option value="{{ $table->id }}" {{ (string) old('ban_an_id', $order->ban_an_id) === (string) $table->id ? 'selected' : '' }}>
                                Bàn {{ $table->so_ban }} ({{ $table->trang_thai }})
                            </option>
                        @endforeach
                    </select>
                    @error('ban_an_id')<div class="form-error">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <span class="card-title">Danh sách món</span>
            <button type="button" id="add-edit-order-item" class="btn btn-secondary btn-sm">+ Thêm món</button>
        </div>
        <div class="card-body">
            <div id="edit-order-items-container" style="display: grid; gap: 10px;">
                @foreach($formItems as $index => $row)
                    <div class="order-item-row" style="border: 1px solid #eee; border-radius: 10px; padding: 10px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                            <strong class="order-item-label">Món {{ $index + 1 }}</strong>
                            <button type="button" class="btn btn-danger btn-sm btn-remove-order-item" style="display: none;">Xóa món</button>
                        </div>

                        <div class="grid" style="grid-template-columns: 1.5fr 1.2fr 0.8fr; gap: 10px;">
                            <div class="form-group" style="margin: 0;">
                                <label class="form-label">Sản phẩm</label>
                                <select class="form-control js-product-select" data-field="san_pham_id" required>
                                    <option value="">Chọn sản phẩm</option>
                                    @foreach($availableProducts ?? [] as $product)
                                        <option value="{{ $product->id }}" {{ (string) ($row['san_pham_id'] ?? '') === (string) $product->id ? 'selected' : '' }}>
                                            {{ $product->ten_san_pham }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group" style="margin: 0;">
                                <label class="form-label">Kích cỡ</label>
                                <select class="form-control js-size-select" data-field="kich_co_id" data-selected="{{ $row['kich_co_id'] ?? '' }}">
                                    <option value="">Không chọn kích cỡ</option>
                                </select>
                            </div>

                            <div class="form-group" style="margin: 0;">
                                <label class="form-label">Số lượng</label>
                                <input type="number" class="form-control" data-field="so_luong" min="1" step="1"
                                       value="{{ $row['so_luong'] ?? 1 }}" required>
                            </div>
                        </div>

                        <div class="form-group" style="margin-top: 8px; margin-bottom: 0;">
                            <label class="form-label">Ghi chú món</label>
                            <input type="text" class="form-control" data-field="ghi_chu_mon" maxlength="255"
                                   value="{{ $row['ghi_chu_mon'] ?? '' }}" placeholder="Ít đá, ít ngọt...">
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="action-row" style="justify-content: flex-end; margin-top: 16px;">
        <a href="{{ route('manager.orders.show', $order->id) }}" class="btn btn-secondary">Hủy</a>
        <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
    </div>
</form>

@if(($order->trang_thai_thanh_toan ?? 'chưa thanh toán') !== 'đã thanh toán' && auth()->check() && in_array(auth()->user()->vai_tro ?? '', ['quản lý', 'nhân viên', 'chủ cửa hàng'], true))
<div id="payment-modal" style="position:fixed;inset:0;display:none;align-items:center;justify-content:center;z-index:10001;padding:20px;" role="dialog" aria-modal="true">
    <div onclick="closePaymentModal()" style="position:absolute;inset:0;background:rgba(18,12,8,0.72);backdrop-filter:blur(2px);"></div>
    <div style="position:relative;width:min(460px,92vw);background:rgba(30,17,6,0.92);border-radius:18px;border:1px solid rgba(240,221,184,0.16);backdrop-filter:blur(14px);padding:28px 26px 22px;box-shadow:0 24px 60px rgba(0,0,0,0.45);font-family:'Outfit',sans-serif;">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;">
            <div style="width:44px;height:44px;border-radius:50%;background:rgba(40, 167, 69, 0.15);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#28a745" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2" y="5" width="20" height="14" rx="2"></rect>
                    <line x1="2" y1="10" x2="22" y2="10"></line>
                </svg>
            </div>
            <div style="font-size:18px;font-weight:700;color:#F0DDB8;">Thanh toán đơn #{{ $order->id }}</div>
            <button type="button" onclick="closePaymentModal()" style="margin-left:auto;background:none;border:none;cursor:pointer;color:rgba(255,255,255,0.5);font-size:20px;line-height:1;">&times;</button>
        </div>

        <form method="POST" action="{{ route('manager.orders.payment', $order->id) }}" id="order-payment-method-form">
            @csrf
            @method('PATCH')
            <input type="hidden" name="trang_thai_thanh_toan" value="{{ $order->trang_thai_thanh_toan ?? 'chưa thanh toán' }}">
            <div style="margin-bottom: 16px;">
                <label style="display:block;font-size:14px;color:rgba(255,255,255,0.78);margin-bottom:8px;">Email nhận hóa đơn (Tuỳ chọn)</label>
                <input type="email" name="email_khach_hang" value="{{ $order->email_khach_hang ?? $order->nguoiDung?->email ?? '' }}" placeholder="email@example.com" style="width:100%;background:rgba(255,255,255,0.05);border:1px solid rgba(240,221,184,0.3);color:#F0DDB8;border-radius:8px;padding:10px;font-family:'Outfit',sans-serif;outline:none;">
            </div>
            <div style="margin-bottom: 20px;">
                <label style="display:block;font-size:14px;color:rgba(255,255,255,0.78);margin-bottom:8px;">Phương thức thanh toán <span style="color:#ff6b6b">*</span></label>
                <select name="phuong_thuc_thanh_toan" id="order-payment-method-select" style="width:100%;background:rgba(255,255,255,0.05);border:1px solid rgba(240,221,184,0.3);color:#F0DDB8;border-radius:8px;padding:10px;font-family:'Outfit',sans-serif;outline:none;" required>
                    <option value="tiền mặt" style="background:#1e1106;color:#F0DDB8;" {{ empty($order->phuong_thuc_thanh_toan) || $order->phuong_thuc_thanh_toan === 'tiền mặt' ? 'selected' : '' }}>Tiền mặt</option>
                    <option value="chuyển khoản" style="background:#1e1106;color:#F0DDB8;" {{ ($order->phuong_thuc_thanh_toan ?? '') === 'chuyển khoản' ? 'selected' : '' }}>Chuyển khoản</option>
                </select>
            </div>
        </form>

        <div id="order-payment-actions" data-paid="{{ ($order->trang_thai_thanh_toan ?? '') === 'đã thanh toán' ? '1' : '0' }}">
            <div id="order-cash-action" style="display: none;">
                <form method="POST" action="{{ route('manager.orders.payment', $order->id) }}" onsubmit="document.getElementById('cash-email-input').value = document.querySelector('input[name=\'email_khach_hang\']').value; return confirmSubmit(this, 'Xác nhận đã nhận tiền mặt cho đơn này?')">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="phuong_thuc_thanh_toan" value="tiền mặt">
                    <input type="hidden" name="trang_thai_thanh_toan" value="đã thanh toán">
                    <input type="hidden" name="email_khach_hang" id="cash-email-input" value="">
                    <button type="submit" style="width:100%;padding:12px;border-radius:8px;border:none;background:#28a745;color:#fff;font-size:14px;font-weight:600;cursor:pointer;font-family:'Outfit',sans-serif;">Xác nhận đã thanh toán</button>
                </form>
            </div>
            <div id="order-transfer-action" style="display: none; text-align: center;">
                <div id="payos-qr-container-manager-order" style="width:100%; height:490px; display:none; position: relative; overflow: hidden; margin-bottom: 16px;">
                    <div style="position: absolute; top: 0; left: 50%; margin-left: -200px; width: 400px; height: 650px; transform: scale(0.75); transform-origin: top center;">
                        <iframe id="payos-qr-iframe-manager-order" src="" style="width:100%; height:100%; border:none; border-radius:12px; display:none;" allow="clipboard-write"></iframe>
                    </div>
                    <p style="font-size:15px;color:#16a34a;font-weight:700;display:none;position:absolute;bottom:0;width:100%;text-align:center;background:#fff;padding:8px 0;" id="payos-success-text-manager-order">Đã thanh toán thành công!</p>
                </div>
                <button type="button" id="btn-generate-payos-manager-order" style="display: inline-flex; justify-content: center; width: 100%; padding: 12px; border-radius: 8px; border: none; background: #007bff; color: #fff; font-size: 14px; font-weight: 600; text-decoration: none; font-family:'Outfit',sans-serif; cursor: pointer;" onclick="generatePayOSQrManagerOrder('{{ $order->ma_don_hang ?? '' }}')">
                    Tạo QR thanh toán PayOS
                </button>
            </div>
        </div>
    </div>
</div>
<script>
    function openPaymentModal() {
        var m = document.getElementById('payment-modal');
        if (m) { m.style.display = 'flex'; document.body.style.overflow = 'hidden'; }
    }
    function closePaymentModal() {
        var m = document.getElementById('payment-modal');
        if (m) { m.style.display = 'none'; document.body.style.overflow = ''; }
    }
</script>
@endif
@endsection

<script id="order-product-map-data" type="application/json">@json($productSizeMap ?? [])</script>

@push('scripts')
@include('partials.payos-payment')
<script>
    const orderProductMap = JSON.parse(
        document.getElementById('order-product-map-data')?.textContent || '{}'
    );

    function updateOrderTableRequirement() {
        const typeInput = document.getElementById('edit-order-type');
        const tableGroup = document.getElementById('edit-order-table-group');
        const tableSelect = document.getElementById('edit-order-table');
        const isOnline = typeInput && typeInput.value === 'đặt hàng trước';

        if (!tableGroup || !tableSelect) return;

        tableSelect.required = !isOnline;
        tableGroup.style.display = isOnline ? 'none' : 'block';
    }

    function syncOrderItemNames() {
        const rows = document.querySelectorAll('#edit-order-items-container .order-item-row');
        rows.forEach((row, index) => {
            row.querySelector('.order-item-label').textContent = `Món ${index + 1}`;

            row.querySelectorAll('[data-field]').forEach((input) => {
                const field = input.getAttribute('data-field');
                input.setAttribute('name', `items[${index}][${field}]`);
            });

            const removeBtn = row.querySelector('.btn-remove-order-item');
            removeBtn.style.display = rows.length === 1 ? 'none' : 'inline-flex';
        });
    }

    function buildSizeOptions(sizeSelect, productId) {
        if (!sizeSelect) return;

        const options = [{ id: '', name: 'Không chọn kích cỡ' }];
        const productInfo = orderProductMap[String(productId)] || orderProductMap[productId];
        if (productInfo && Array.isArray(productInfo.sizes)) {
            productInfo.sizes.forEach((size) => options.push({ id: size.id, name: size.name }));
        }

        const selectedValue = sizeSelect.dataset.selected || sizeSelect.value;
        sizeSelect.innerHTML = options
            .map((opt) => `<option value="${opt.id}">${opt.name}</option>`)
            .join('');

        if (selectedValue && options.some((opt) => String(opt.id) === String(selectedValue))) {
            sizeSelect.value = selectedValue;
        }
    }

    function createOrderItemRow() {
        const container = document.getElementById('edit-order-items-container');
        const firstRow = container.querySelector('.order-item-row');
        if (!firstRow) return;

        const newRow = firstRow.cloneNode(true);

        newRow.querySelectorAll('input').forEach((input) => {
            if (input.type === 'number') {
                input.value = '1';
            } else {
                input.value = '';
            }
        });

        newRow.querySelectorAll('select').forEach((select) => {
            select.selectedIndex = 0;
        });

        const sizeSelect = newRow.querySelector('.js-size-select');
        if (sizeSelect) {
            sizeSelect.dataset.selected = '';
            sizeSelect.innerHTML = '<option value="">Không chọn kích cỡ</option>';
        }

        container.appendChild(newRow);
        syncOrderItemNames();
    }

    document.addEventListener('change', function (event) {
        if (event.target && event.target.matches('.js-product-select')) {
            const row = event.target.closest('.order-item-row');
            const sizeSelect = row ? row.querySelector('.js-size-select') : null;
            if (sizeSelect) {
                sizeSelect.dataset.selected = '';
            }
            buildSizeOptions(sizeSelect, event.target.value);
        }
    });

    document.addEventListener('click', function (event) {
        if (event.target && event.target.id === 'add-edit-order-item') {
            createOrderItemRow();
        }

        if (event.target && event.target.matches('.btn-remove-order-item')) {
            const row = event.target.closest('.order-item-row');
            const container = document.getElementById('edit-order-items-container');
            if (!row || !container) return;
            row.remove();
            syncOrderItemNames();
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        updateOrderTableRequirement();
        syncOrderItemNames();

        document.querySelectorAll('.js-product-select').forEach((select) => {
            const row = select.closest('.order-item-row');
            const sizeSelect = row ? row.querySelector('.js-size-select') : null;
            buildSizeOptions(sizeSelect, select.value);
        });
    });
</script>
<script>
(function () {
    const methodSelect = document.getElementById('order-payment-method-select');
    const actions = document.getElementById('order-payment-actions');
    if (!actions) {
        return;
    }

    const paid = actions.dataset.paid === '1';
    const cashAction = document.getElementById('order-cash-action');
    const transferAction = document.getElementById('order-transfer-action');
    const hint = document.getElementById('order-payment-hint');
    const paidNote = document.getElementById('order-payment-paid');

    function updateActions() {
        if (paid) {
            if (paidNote) paidNote.style.display = 'block';
            if (cashAction) cashAction.style.display = 'none';
            if (transferAction) transferAction.style.display = 'none';
            if (hint) hint.style.display = 'none';
            return;
        }

        const method = methodSelect ? methodSelect.value : '';
        if (cashAction) cashAction.style.display = method === 'tiền mặt' ? 'block' : 'none';
        if (transferAction) transferAction.style.display = method === 'chuyển khoản' ? 'block' : 'none';
        if (hint) hint.style.display = method ? 'none' : 'block';
        if (paidNote) paidNote.style.display = 'none';
    }

    updateActions();

    if (methodSelect) {
        methodSelect.addEventListener('change', updateActions);
    }
})();

// Đơn đã thanh toán sẽ bị khoá sửa → sau khi thanh toán chuyển sang trang chi tiết (tránh 403).
function generatePayOSQrManagerOrder(orderCode) {
    const emailInput = document.querySelector('input[name="email_khach_hang"]');
    PayOSPayment.start({
        orderCode: orderCode,
        source: 'manager',
        email: emailInput ? emailInput.value : '',
        button: document.getElementById('btn-generate-payos-manager-order'),
        iframe: document.getElementById('payos-qr-iframe-manager-order'),
        container: document.getElementById('payos-qr-container-manager-order'),
        successText: document.getElementById('payos-success-text-manager-order'),
        onPaid: function () { window.location.href = '{{ route('manager.orders.show', $order->id) }}'; }
    });
}

</script>
@endpush
