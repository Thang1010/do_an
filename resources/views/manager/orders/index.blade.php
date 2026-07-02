@extends('manager.layout.app')

@section('title', 'Quản lý đơn hàng')
@section('breadcrumb', 'Kinh doanh / <strong>Quản lý đơn hàng</strong>')

@section('content')

    <div class="page-header">
        <div>
            <h1 class="page-title">Quản lý đơn hàng</h1>
            <p class="page-subtitle">Tổng số {{ $totalOrders ?? 0 }} đơn hàng</p>
        </div>
        <div class="page-actions">
            <button class="btn btn-primary" onclick="openModal('create-order-modal')">Thêm đơn hàng</button>
        </div>
    </div>

    {{-- Status tabs --}}
    <div class="tab-container">
        <div class="tab-list">
            @php
                $payStatuses = [
                    '' => ['label' => 'Tất cả', 'count' => $countAll ?? 0],
                    'chưa thanh toán' => ['label' => 'Chưa thanh toán', 'count' => $countUnpaid ?? 0],
                    'đã thanh toán' => ['label' => 'Đã thanh toán', 'count' => $countPaid ?? 0],
                ];
                $currentPayStatus = request('pay_status', '');
            @endphp
            @foreach($payStatuses as $val => $info)
                <a href="{{ route('manager.orders.index', array_merge(request()->except('pay_status', 'page'), $val ? ['pay_status' => $val] : [])) }}"
                    class="tab-btn {{ $currentPayStatus === $val ? 'active' : '' }}">
                    {{ $info['label'] }}
                </a>
            @endforeach
        </div>
    </div>

    {{-- Filter bar --}}
    <div class="filter-bar">
        <form method="GET" action="{{ route('manager.orders.index') }}" class="flex-gap-10">
            @if(request('pay_status'))
                <input type="hidden" name="pay_status" value="{{ request('pay_status') }}">
            @endif
            <input type="text" name="search" class="form-control filter-search" placeholder="Tìm mã đơn / tên khách..."
                value="{{ request('search') }}">
            <input type="date" name="tu_ngay" class="form-control" value="{{ request('tu_ngay') }}">
            <input type="date" name="den_ngay" class="form-control" value="{{ request('den_ngay') }}">
            <select name="nhan_vien_id" class="form-control">
                <option value="">Tất cả nhân viên</option>
                @foreach($nhanViens ?? [] as $nv)
                    <option value="{{ $nv->id }}" {{ request('nhan_vien_id') == $nv->id ? 'selected' : '' }}>
                        {{ $nv->ho_ten }}
                    </option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-primary">Lọc</button>
            <a href="{{ route('manager.orders.index') }}" class="btn btn-secondary">Xóa lọc</a>
        </form>
    </div>

    <div class="modal-backdrop" id="create-order-modal" data-auto-open="{{ ($errors->any() && old('items')) ? '1' : '0' }}">
        <div class="modal-box" style="max-width: 900px; width: calc(100% - 32px);">
            <div class="modal-header">
                <span class="modal-title">Tạo đơn hàng mới</span>
                <button class="modal-close" onclick="closeModal('create-order-modal')">&#x2715;</button>
            </div>
            <div class="modal-body">
                <form id="create-order-form" method="POST" action="{{ route('manager.orders.store') }}">
                    @csrf

                    <div class="grid" style="grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px;">
                        <div class="form-group">
                            <label class="form-label">Loại đơn</label>
                            <select name="loai_don" id="create-order-type" class="form-control" required>
                                <option value="sử dụng ngay" {{ old('loai_don') === 'sử dụng ngay' ? 'selected' : '' }}>Sử dụng ngay (tại quán)</option>
                                <option value="đặt hàng trước" {{ old('loai_don') === 'đặt hàng trước' ? 'selected' : '' }}>Đặt trước (ngồi tại quán, hẹn giờ)</option>
                                <option value="mang về" {{ old('loai_don') === 'mang về' ? 'selected' : '' }}>Mang về</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Khách hàng</label>
                            <select name="nguoi_dung_id" class="form-control">
                                <option value="">Khách vãng lai</option>
                                @foreach($customers ?? [] as $customer)
                                    <option value="{{ $customer->id }}" {{ (string) old('nguoi_dung_id') === (string) $customer->id ? 'selected' : '' }}>
                                        {{ $customer->ho_ten }}{{ $customer->so_dien_thoai ? ' - ' . $customer->so_dien_thoai : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group" id="create-order-table-group">
                            <label class="form-label">Bàn ăn <span>*</span></label>
                            <select name="ban_an_id" id="create-order-table" class="form-control" required>
                                <option value="">Chọn bàn</option>
                                @foreach($banAns ?? [] as $table)
                                    @if($table->trang_thai !== 'ngưng sử dụng')
                                        <option value="{{ $table->id }}"
                                            data-trang-thai="{{ $table->trang_thai }}"
                                            {{ (string) old('ban_an_id') === (string) $table->id ? 'selected' : '' }}>
                                            Bàn {{ $table->so_ban }}@if($table->trang_thai !== 'trống') — {{ $table->trang_thai }}@endif
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                            <small class="text-muted" id="create-order-table-hint" style="display:none;">Đặt trước chỉ chọn được bàn đang trống.</small>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Phương thức thanh toán</label>
                            <select name="phuong_thuc_thanh_toan" class="form-control">
                                <option value="">Chưa chọn</option>
                                <option value="tiền mặt" {{ old('phuong_thuc_thanh_toan') === 'tiền mặt' ? 'selected' : '' }}>
                                    Tiền mặt</option>
                                <option value="chuyển khoản" {{ old('phuong_thuc_thanh_toan') === 'chuyển khoản' ? 'selected' : '' }}>Chuyển khoản</option>
                            </select>
                        </div>

                    </div>

                    <div class="form-group" id="create-order-time-group" style="display: none;">
                        <label class="form-label">Hẹn giờ đến <span>*</span></label>
                        <input type="time" name="thoi_gian_den" id="create-order-time" class="form-control"
                            value="{{ old('thoi_gian_den') }}">
                        <small class="text-muted">Khách đặt trước và ngồi dùng tại quán theo giờ hẹn (trong ngày).</small>
                    </div>



                    <div style="display: flex; align-items: center; justify-content: space-between; margin: 16px 0 8px;">
                        <label class="form-label" style="margin: 0;">Danh sách món <span>*</span></label>
                        <button type="button" id="add-order-item" class="btn btn-secondary btn-sm">+ Thêm món</button>
                    </div>

                    <div id="order-items-container" style="display: grid; gap: 10px;">
                        <div class="order-item-row" style="border: 1px solid #eee; border-radius: 10px; padding: 10px;">
                            <div
                                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                <strong class="order-item-label">Món 1</strong>
                                <div style="display: flex; align-items: center; gap: 12px; margin-left: auto;">
                                    <span class="item-price-display price-text text-primary" style="font-size: 15px; font-weight: 700;">0đ</span>
                                    <button type="button" class="btn btn-danger btn-sm btn-remove-order-item"
                                        style="display: none;">Xóa món</button>
                                </div>
                            </div>

                            <div class="form-group" style="margin: 0 0 10px;">
                                <label class="form-label">Danh mục (lọc sản phẩm)</label>
                                <div class="clearable-select">
                                    <select class="form-control js-category-select">
                                        <option value="">Tất cả danh mục</option>
                                        @foreach($categories ?? [] as $category)
                                            <option value="{{ $category->id }}">{{ $category->ten_danh_muc }}</option>
                                        @endforeach
                                    </select>
                                    <button type="button" class="clearable-clear" tabindex="-1" aria-label="Bỏ chọn danh mục">&times;</button>
                                </div>
                            </div>

                            <div class="grid" style="grid-template-columns: 1.4fr 1fr 1fr 0.7fr; gap: 10px;">
                                <div class="form-group" style="margin: 0;">
                                    <label class="form-label">Sản phẩm</label>
                                    <div class="clearable-select">
                                        <select class="form-control js-product-select" data-field="san_pham_id" required>
                                            <option value="">Chọn sản phẩm</option>
                                            @foreach($availableProducts ?? [] as $product)
                                                <option value="{{ $product->id }}" data-danh-muc="{{ $product->danh_muc_id }}" {{ (string) old('items.0.san_pham_id') === (string) $product->id ? 'selected' : '' }}>
                                                    {{ $product->ten_san_pham }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <button type="button" class="clearable-clear" tabindex="-1" aria-label="Bỏ chọn sản phẩm">&times;</button>
                                    </div>
                                </div>

                                <div class="form-group" style="margin: 0;">
                                    <label class="form-label">Kích cỡ</label>
                                    <select class="form-control js-size-select" data-field="kich_co_id">
                                        <option value="">Không chọn kích cỡ</option>
                                    </select>
                                </div>

                                <div class="form-group" style="margin: 0;">
                                    <label class="form-label">Nhiệt độ</label>
                                    <select class="form-control js-temp-select">
                                        <option value="">Không chọn nhiệt độ</option>
                                    </select>
                                </div>

                                <div class="form-group" style="margin: 0;">
                                    <label class="form-label">Số lượng</label>
                                    <input type="number" class="form-control" data-field="so_luong" min="1" step="1"
                                        value="{{ old('items.0.so_luong', 1) }}" required>
                                </div>
                            </div>

                            <div class="form-group" style="margin-top: 8px; margin-bottom: 0;">
                                <label class="form-label">Ghi chú món</label>
                                <input type="text" class="form-control" data-field="ghi_chu_mon" maxlength="255"
                                    value="{{ old('items.0.ghi_chu_mon') }}" placeholder="Ít đá, ít ngọt...">
                            </div>
                        </div>
                    </div>
                    <div style="margin-top: 20px; border: 1px solid rgba(240, 221, 184, 0.12); border-radius: 10px; background: rgba(255, 255, 255, 0.03); display: flex; justify-content: flex-end; align-items: center; gap: 16px; padding: 14px 20px;">
                        <span style="font-size: 14px; font-weight: 600; color: #a89f91;">Tổng cộng giá trị đơn:</span>
                        <span id="order-grand-total" style="font-size: 20px; font-weight: 700; color: #f0ddb8;">0đ</span>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('create-order-modal')">Hủy</button>
                <button type="submit" form="create-order-form" class="btn btn-primary">Lưu đơn hàng</button>
            </div>
        </div>
    </div>

    {{-- Orders table --}}
    <div id="orders-list-wrap">
        @include('manager.orders.partials.list')
    </div>

@endsection

<script id="order-product-map-data" type="application/json">@json($productSizeMap ?? [])</script>

@push('scripts')
    <script>
        // ── Polling danh sách đơn: đơn mới/cập nhật thanh toán tự hiện, không cần F5 ──
        (function () {
            var INTERVAL = 12000; // 12 giây
            var inFlight = false;
            var wrap = document.getElementById('orders-list-wrap');
            if (!wrap) return;

            function refresh() {
                if (inFlight || document.hidden) return;
                // Bỏ qua khi đang mở modal hoặc đang gõ trong ô input/select
                if (document.querySelector('.modal-backdrop.open, .modal-backdrop[style*="flex"]')) return;
                var activeEl = document.activeElement;
                if (activeEl && ['INPUT', 'TEXTAREA', 'SELECT'].includes(activeEl.tagName)) return;

                inFlight = true;
                fetch(window.location.href, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'X-Partial': '1' } })
                    .then(function (r) { return r.ok ? r.json() : null; })
                    .then(function (data) {
                        if (data && typeof data.html === 'string') wrap.innerHTML = data.html;
                    })
                    .catch(function () { /* im lặng */ })
                    .finally(function () { inFlight = false; });
            }

            setInterval(refresh, INTERVAL);
        })();

        const orderProductMap = JSON.parse(
            document.getElementById('order-product-map-data')?.textContent || '{}'
        );
        const shouldOpenCreateOrderModal =
            document.getElementById('create-order-modal')?.dataset.autoOpen === '1';

        function updateOrderTypeFields() {
            const typeSelect = document.getElementById('create-order-type');
            const tableSelect = document.getElementById('create-order-table');
            const tableGroup = document.getElementById('create-order-table-group');
            const tableHint = document.getElementById('create-order-table-hint');
            const timeGroup = document.getElementById('create-order-time-group');
            const timeInput = document.getElementById('create-order-time');
            if (!typeSelect || !tableSelect) return;

            const type = typeSelect.value;
            const isTakeaway = type === 'mang về';
            const isBooking = type === 'đặt hàng trước';

            // Bàn: cần cho "sử dụng ngay" và "đặt trước"; ẩn với "mang về".
            if (tableGroup) tableGroup.style.display = isTakeaway ? 'none' : 'block';
            tableSelect.required = !isTakeaway;

            // "Đặt trước" chỉ được chọn bàn TRỐNG → lọc option; các loại khác hiện mọi bàn dùng được.
            Array.from(tableSelect.options).forEach(function (opt) {
                if (!opt.value) return;
                var st = opt.getAttribute('data-trang-thai');
                var hide = isBooking && st && st !== 'trống';
                opt.hidden = hide;
                opt.disabled = hide;
            });
            if (isBooking && tableSelect.selectedOptions[0] && tableSelect.selectedOptions[0].disabled) {
                tableSelect.value = '';
            }
            if (tableHint) tableHint.style.display = isBooking ? 'block' : 'none';

            // Hẹn giờ đến: chỉ cho "đặt trước".
            if (timeGroup) timeGroup.style.display = isBooking ? 'block' : 'none';
            if (timeInput) timeInput.required = isBooking;
        }

        function syncOrderItemNames() {
            const rows = document.querySelectorAll('#order-items-container .order-item-row');
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

            const previousValue = sizeSelect.value;
            sizeSelect.innerHTML = options
                .map((opt) => `<option value="${opt.id}">${opt.name}</option>`)
                .join('');

            if (previousValue && options.some((opt) => String(opt.id) === String(previousValue))) {
                sizeSelect.value = previousValue;
            }
        }

        function updateRowPrice(row) {
            const productSelect = row.querySelector('.js-product-select');
            const sizeSelect = row.querySelector('.js-size-select');
            const qtyInput = row.querySelector('[data-field="so_luong"]');
            const priceDisplay = row.querySelector('.item-price-display');

            if (!productSelect || !priceDisplay) return;

            const productId = productSelect.value;
            if (!productId) {
                priceDisplay.textContent = '0đ';
                return;
            }

            const productInfo = orderProductMap[String(productId)] || orderProductMap[productId];
            if (!productInfo) {
                priceDisplay.textContent = '0đ';
                return;
            }

            let unitPrice = parseFloat(productInfo.base_price || 0);
            const sizeId = sizeSelect ? sizeSelect.value : '';
            if (sizeId && Array.isArray(productInfo.sizes)) {
                const sizeItem = productInfo.sizes.find(s => String(s.id) === String(sizeId));
                if (sizeItem && sizeItem.price !== undefined) {
                    unitPrice = parseFloat(sizeItem.price);
                }
            }

            const qty = parseInt(qtyInput ? qtyInput.value : 1) || 1;
            const total = unitPrice * qty;

            const formatCurrency = (val) => new Intl.NumberFormat('vi-VN').format(val) + 'đ';

            if (qty > 1) {
                priceDisplay.textContent = `${formatCurrency(unitPrice)} x ${qty} = ${formatCurrency(total)}`;
            } else {
                priceDisplay.textContent = formatCurrency(unitPrice);
            }
        }

        function updateGrandTotal() {
            const rows = document.querySelectorAll('.order-item-row');
            let grandTotal = 0;

            rows.forEach(row => {
                const productSelect = row.querySelector('.js-product-select');
                const sizeSelect = row.querySelector('.js-size-select');
                const qtyInput = row.querySelector('[data-field="so_luong"]');

                if (!productSelect) return;

                const productId = productSelect.value;
                if (!productId) return;

                const productInfo = orderProductMap[String(productId)] || orderProductMap[productId];
                if (!productInfo) return;

                let unitPrice = parseFloat(productInfo.base_price || 0);
                const sizeId = sizeSelect ? sizeSelect.value : '';
                if (sizeId && Array.isArray(productInfo.sizes)) {
                    const sizeItem = productInfo.sizes.find(s => String(s.id) === String(sizeId));
                    if (sizeItem && sizeItem.price !== undefined) {
                        unitPrice = parseFloat(sizeItem.price);
                    }
                }

                const qty = parseInt(qtyInput ? qtyInput.value : 1) || 1;
                grandTotal += unitPrice * qty;
            });

            const totalEl = document.getElementById('order-grand-total');
            if (totalEl) {
                totalEl.textContent = new Intl.NumberFormat('vi-VN').format(grandTotal) + 'đ';
            }
        }

        function createOrderItemRow() {
            const container = document.getElementById('order-items-container');
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
                sizeSelect.innerHTML = '<option value="">Không chọn kích cỡ</option>';
            }

            const priceDisplay = newRow.querySelector('.item-price-display');
            if (priceDisplay) {
                priceDisplay.textContent = '0đ';
            }

            container.appendChild(newRow);
            syncOrderItemNames();
        }

        document.addEventListener('change', function (event) {
            if (event.target && event.target.matches('#create-order-type')) {
                updateOrderTypeFields();
            }

            if (event.target && event.target.matches('.js-product-select')) {
                const row = event.target.closest('.order-item-row');
                const sizeSelect = row ? row.querySelector('.js-size-select') : null;
                buildSizeOptions(sizeSelect, event.target.value);
                if (row) {
                    updateRowPrice(row);
                    updateGrandTotal();
                }
            }

            if (event.target && event.target.matches('.js-size-select')) {
                const row = event.target.closest('.order-item-row');
                if (row) {
                    updateRowPrice(row);
                    updateGrandTotal();
                }
            }
        });

        document.addEventListener('input', function (event) {
            if (event.target && event.target.matches('[data-field="so_luong"]')) {
                const row = event.target.closest('.order-item-row');
                if (row) {
                    updateRowPrice(row);
                    updateGrandTotal();
                }
            }
        });

        document.addEventListener('click', function (event) {
            if (event.target && event.target.id === 'add-order-item') {
                createOrderItemRow();
                updateGrandTotal();
            }

            if (event.target && event.target.matches('.btn-remove-order-item')) {
                const row = event.target.closest('.order-item-row');
                const container = document.getElementById('order-items-container');
                if (!row || !container) return;
                row.remove();
                syncOrderItemNames();
                updateGrandTotal();
            }
        });

        document.addEventListener('DOMContentLoaded', function () {
            syncOrderItemNames();
            updateOrderTypeFields();

            document.querySelectorAll('.js-product-select').forEach((select) => {
                const row = select.closest('.order-item-row');
                const sizeSelect = row ? row.querySelector('.js-size-select') : null;
                buildSizeOptions(sizeSelect, select.value);
                if (row) {
                    updateRowPrice(row);
                }
            });
            updateGrandTotal();

            if (shouldOpenCreateOrderModal) {
                openModal('create-order-modal');
            }
        });
    </script>

@include('partials.order-items-enhancer')
@endpush