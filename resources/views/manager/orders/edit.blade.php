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
        <div class="card-header"><span class="card-title">Thông tin đơn hàng</span></div>
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

@if(auth()->check() && in_array(auth()->user()->vai_tro ?? '', ['quản lý', 'nhân viên', 'chủ cửa hàng'], true))
<div class="card mt-20">
    <div class="card-header">
        <span class="card-title">Thanh toán</span>
    </div>
    <div class="card-body">
        <div class="form-grid-2">
            <div>
                <div class="text-12 text-muted">Phương thức thanh toán</div>
                <div class="font-600">{{ $order->phuong_thuc_thanh_toan ?? '—' }}</div>
            </div>
            <div>
                <div class="text-12 text-muted">Trạng thái thanh toán</div>
                <div class="font-600"><span class="badge {{ $paymentStatusClass }}">{{ $paymentStatus }}</span></div>
            </div>
        </div>

        <div style="margin-top: 18px; border-top: 1px dashed #e6ded2; padding-top: 14px;">
            <div class="text-12 text-muted" style="margin-bottom: 10px;">Cập nhật thanh toán (bắt buộc chọn phương thức)</div>
            <form method="POST" action="{{ route('manager.orders.payment', $order->id) }}" id="order-payment-method-form">
                @csrf
                @method('PATCH')
                <input type="hidden" name="trang_thai_thanh_toan" value="{{ $order->trang_thai_thanh_toan ?? 'chưa thanh toán' }}">
                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Phương thức thanh toán <span>*</span></label>
                        <select name="phuong_thuc_thanh_toan" id="order-payment-method-select" class="form-control" required>
                            <option value="" disabled {{ empty($order->phuong_thuc_thanh_toan) ? 'selected' : '' }}>Chọn phương thức</option>
                            <option value="tiền mặt" {{ ($order->phuong_thuc_thanh_toan ?? '') === 'tiền mặt' ? 'selected' : '' }}>Tiền mặt</option>
                            <option value="chuyển khoản" {{ ($order->phuong_thuc_thanh_toan ?? '') === 'chuyển khoản' ? 'selected' : '' }}>Chuyển khoản</option>
                        </select>
                    </div>
                    <div class="form-group" style="display: flex; align-items: flex-end;">
                        <button type="submit" class="btn btn-secondary btn-sm">Lưu phương thức</button>
                    </div>
                </div>
            </form>

            <div id="order-payment-actions" data-paid="{{ ($order->trang_thai_thanh_toan ?? '') === 'đã thanh toán' ? '1' : '0' }}">
                <div id="order-payment-paid" class="text-12 text-muted" style="margin-top: 10px; display: none;">
                    Đơn hàng đã thanh toán thành công.
                </div>
                <div id="order-payment-hint" class="text-12 text-muted" style="margin-top: 10px; display: none;">
                    Vui lòng chọn phương thức thanh toán để tiếp tục.
                </div>
                <div id="order-cash-action" style="margin-top: 12px; display: none;">
                    <form method="POST" action="{{ route('manager.orders.payment', $order->id) }}"
                          onsubmit="return confirm('Xác nhận đã nhận tiền mặt cho đơn này?')">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="phuong_thuc_thanh_toan" value="tiền mặt">
                        <input type="hidden" name="trang_thai_thanh_toan" value="đã thanh toán">
                        <button type="submit" class="btn btn-primary btn-sm">Xác nhận đã thanh toán</button>
                    </form>
                </div>
                <div id="order-transfer-action" style="margin-top: 12px; display: none;">
                    <div class="text-12 text-muted" style="margin-bottom: 10px;">Tạo mã QR thanh toán nhanh (hiệu lực 60 giây)</div>
                    <button type="button"
                            class="btn btn-primary btn-sm"
                            id="order-generate-payment-qr-btn"
                            data-url="{{ route('manager.orders.payment-qr', $order->id) }}">
                        Tạo mã QR thanh toán
                    </button>

                    <div id="order-payment-qr-message" class="text-12 text-muted" style="margin-top: 10px;">Nhấn nút để tạo mã QR thanh toán.</div>

                    <div id="order-payment-qr-panel" style="display: none; margin-top: 12px;">
                        <img id="order-payment-qr-image" src="" alt="QR thanh toán" style="width: 220px; height: 220px; border-radius: 10px; border: 1px solid #e6ded2; background: #fff;">
                        <div class="text-12" style="margin-top: 10px;">
                            <div><strong>Ngân hàng:</strong> <span id="order-payment-qr-bank">—</span></div>
                            <div><strong>Số tài khoản:</strong> <span id="order-payment-qr-account">—</span></div>
                            <div><strong>Số tiền:</strong> <span id="order-payment-qr-amount">0đ</span></div>
                            <div><strong>Nội dung CK:</strong> <span id="order-payment-qr-content">—</span></div>
                            <div style="margin-top: 6px;"><strong>Hết hiệu lực sau:</strong> <span id="order-payment-qr-countdown">60</span>s</div>
                        </div>

                        <form id="order-confirm-qr-paid-form"
                              method="POST"
                              action="{{ route('manager.orders.payment', $order->id) }}"
                              style="margin-top: 10px;"
                              onsubmit="return confirm('Xác nhận đã nhận tiền qua QR cho đơn này?')">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="phuong_thuc_thanh_toan" value="chuyển khoản">
                            <input type="hidden" name="trang_thai_thanh_toan" value="đã thanh toán">
                            <button type="submit" class="btn btn-secondary btn-sm">Xác nhận đã nhận tiền QR</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif
@endsection

<script id="order-product-map-data" type="application/json">@json($productSizeMap ?? [])</script>

@push('scripts')
<script>
    const orderProductMap = JSON.parse(
        document.getElementById('order-product-map-data')?.textContent || '{}'
    );

    function updateOrderTableRequirement() {
        const typeInput = document.getElementById('edit-order-type');
        const tableGroup = document.getElementById('edit-order-table-group');
        const tableSelect = document.getElementById('edit-order-table');
        const isOnline = typeInput && typeInput.value === 'đặt online';

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

(function () {
    const btn = document.getElementById('order-generate-payment-qr-btn');
    if (!btn) {
        return;
    }

    const panel = document.getElementById('order-payment-qr-panel');
    const message = document.getElementById('order-payment-qr-message');
    const qrImage = document.getElementById('order-payment-qr-image');
    const bankEl = document.getElementById('order-payment-qr-bank');
    const accountEl = document.getElementById('order-payment-qr-account');
    const amountEl = document.getElementById('order-payment-qr-amount');
    const contentEl = document.getElementById('order-payment-qr-content');
    const countdownEl = document.getElementById('order-payment-qr-countdown');
    let countdownTimer = null;

    function setMessage(text, isError) {
        message.textContent = text;
        message.style.color = isError ? '#b42318' : '#5f544a';
    }

    function stopCountdown() {
        if (countdownTimer) {
            clearInterval(countdownTimer);
            countdownTimer = null;
        }
    }

    function startCountdown(seconds) {
        stopCountdown();
        let remain = Number(seconds) || 60;
        countdownEl.textContent = remain;

        countdownTimer = setInterval(function () {
            remain -= 1;
            countdownEl.textContent = Math.max(remain, 0);
            if (remain <= 0) {
                stopCountdown();
                panel.style.display = 'none';
                qrImage.src = '';
                setMessage('Mã QR đã hết hiệu lực. Vui lòng tạo mã mới.', true);
            }
        }, 1000);
    }

    btn.addEventListener('click', async function () {
        btn.disabled = true;
        setMessage('Đang tạo mã QR thanh toán...', false);

        try {
            const response = await fetch(btn.dataset.url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const payload = await response.json();

            if (!response.ok) {
                throw new Error(payload.message || 'Không thể tạo mã QR thanh toán.');
            }

            qrImage.src = payload.qr_url;
            bankEl.textContent = payload.bank_name || payload.bank_code || '—';
            accountEl.textContent = payload.account_no || '—';
            amountEl.textContent = new Intl.NumberFormat('vi-VN').format(payload.amount || 0) + 'đ';
            contentEl.textContent = payload.transfer_content || '—';
            panel.style.display = 'block';
            startCountdown(payload.expires_in || 60);
            setMessage(payload.message || 'Đã tạo mã QR thanh toán.', false);
        } catch (error) {
            panel.style.display = 'none';
            qrImage.src = '';
            stopCountdown();
            setMessage(error.message || 'Có lỗi khi tạo mã QR.', true);
        } finally {
            btn.disabled = false;
        }
    });
})();
</script>
@endpush
