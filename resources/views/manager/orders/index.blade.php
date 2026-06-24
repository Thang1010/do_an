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
                                <option value="sử dụng ngay" {{ old('loai_don') === 'sử dụng ngay' ? 'selected' : '' }}>Sử dụng ngay</option>
                                <option value="đặt hàng trước" {{ old('loai_don') === 'đặt hàng trước' ? 'selected' : '' }}>Đặt hàng trước</option>
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
                                    @if($table->trang_thai === 'trống')
                                        <option value="{{ $table->id }}" {{ (string) old('ban_an_id') === (string) $table->id ? 'selected' : '' }}>
                                            Bàn {{ $table->so_ban }}
                                        </option>
                                    @endif
                                @endforeach
                            </select>
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

                    <div class="form-group" id="create-order-address-group" style="display: none;">
                        <label class="form-label">Địa chỉ giao hàng (đơn online)</label>
                        <textarea name="ghi_chu_them" class="form-control"
                            rows="2"></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Ghi chú đơn</label>
                        <textarea name="ghi_chu" class="form-control" rows="2">{{ old('ghi_chu') }}</textarea>
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
                                <button type="button" class="btn btn-danger btn-sm btn-remove-order-item"
                                    style="display: none;">Xóa món</button>
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
            const addressGroup = document.getElementById('create-order-address-group');
            const isOnline = typeSelect && typeSelect.value === 'đặt hàng trước';

            if (!tableSelect || !addressGroup) return;

            tableSelect.required = !isOnline;
            if (isOnline) {
                addressGroup.style.display = 'block';
            } else {
                addressGroup.style.display = 'none';
            }
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
            }
        });

        document.addEventListener('click', function (event) {
            if (event.target && event.target.id === 'add-order-item') {
                createOrderItemRow();
            }

            if (event.target && event.target.matches('.btn-remove-order-item')) {
                const row = event.target.closest('.order-item-row');
                const container = document.getElementById('order-items-container');
                if (!row || !container) return;
                row.remove();
                syncOrderItemNames();
            }
        });

        document.addEventListener('DOMContentLoaded', function () {
            syncOrderItemNames();
            updateOrderTypeFields();

            document.querySelectorAll('.js-product-select').forEach((select) => {
                const row = select.closest('.order-item-row');
                const sizeSelect = row ? row.querySelector('.js-size-select') : null;
                buildSizeOptions(sizeSelect, select.value);
            });

            if (shouldOpenCreateOrderModal) {
                openModal('create-order-modal');
            }
        });
    </script>

@include('partials.order-items-enhancer')
@endpush