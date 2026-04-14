@extends('layouts.manager')

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
            $statuses = [
                ''                => ['label' => 'Tất cả', 'count' => $countAll ?? 0],
                'chờ xác nhận'    => ['label' => 'Chờ xác nhận', 'count' => $countPending ?? 0],
                'đã xác nhận'      => ['label' => 'Đã xác nhận', 'count' => $countConfirmed ?? 0],
                'đã hủy'           => ['label' => 'Đã hủy', 'count' => $countCancelled ?? 0],
            ];
            $currentStatusRaw = request('status', '');
            $currentStatus = match ($currentStatusRaw) {
                'cho_xac_nhan' => 'chờ xác nhận',
                'dang_pha_che', 'hoan_thanh', 'da_giao' => 'đã xác nhận',
                'huy' => 'đã hủy',
                default => $currentStatusRaw,
            };
        @endphp
        @foreach($statuses as $val => $info)
        <a href="{{ route('manager.orders.index', array_merge(request()->except('status','page'), $val ? ['status'=>$val] : [])) }}"
           class="tab-btn {{ $currentStatus === $val ? 'active' : '' }}">
            {{ $info['label'] }}
            @if($info['count'] > 0)
                <span class="tab-count {{ $currentStatus === $val ? 'tab-count-active' : 'tab-count-default' }}">
                    {{ $info['count'] }}
                </span>
            @endif
        </a>
        @endforeach
    </div>
</div>

{{-- Filter bar --}}
<div class="filter-bar">
    <form method="GET" action="{{ route('manager.orders.index') }}"
          class="flex-gap-10">
        @if(request('status'))
            <input type="hidden" name="status" value="{{ request('status') }}">
        @endif
        <input type="text" name="search" class="form-control filter-search"
               placeholder="Tìm mã đơn / tên khách..." value="{{ request('search') }}">
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
                        <label class="form-label">Loại đơn <span>*</span></label>
                        <select name="loai_don" id="create-order-type" class="form-control" required>
                            <option value="mua tại quán" {{ old('loai_don') === 'mua tại quán' ? 'selected' : '' }}>Mua tại quán</option>
                            <option value="gọi tại bàn bằng qr" {{ old('loai_don') === 'gọi tại bàn bằng qr' ? 'selected' : '' }}>Gọi tại bàn bằng QR</option>
                            <option value="đặt online" {{ old('loai_don') === 'đặt online' ? 'selected' : '' }}>Đặt online</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Khách hàng thành viên</label>
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
                        <label class="form-label">Bàn ăn</label>
                        <select name="ban_an_id" id="create-order-table" class="form-control">
                            <option value="">Chọn bàn</option>
                            @foreach($banAns ?? [] as $table)
                                <option value="{{ $table->id }}" {{ (string) old('ban_an_id') === (string) $table->id ? 'selected' : '' }}>
                                    Bàn {{ $table->so_ban }} ({{ $table->trang_thai }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Phương thức thanh toán</label>
                        <select name="phuong_thuc_thanh_toan" class="form-control">
                            <option value="">Chưa chọn</option>
                            <option value="tiền mặt" {{ old('phuong_thuc_thanh_toan') === 'tiền mặt' ? 'selected' : '' }}>Tiền mặt</option>
                            <option value="chuyển khoản" {{ old('phuong_thuc_thanh_toan') === 'chuyển khoản' ? 'selected' : '' }}>Chuyển khoản</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Tên khách hàng</label>
                        <input type="text" name="ten_khach_hang" class="form-control" maxlength="150" value="{{ old('ten_khach_hang') }}">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Số điện thoại khách</label>
                        <input type="text" name="so_dien_thoai_khach" class="form-control" maxlength="20" value="{{ old('so_dien_thoai_khach') }}">
                    </div>
                </div>

                <div class="form-group" id="create-order-address-group" style="display: none;">
                    <label class="form-label">Địa chỉ giao hàng (đơn online)</label>
                    <textarea name="dia_chi_giao_hang" class="form-control" rows="2">{{ old('dia_chi_giao_hang') }}</textarea>
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
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                            <strong class="order-item-label">Món 1</strong>
                            <button type="button" class="btn btn-danger btn-sm btn-remove-order-item" style="display: none;">Xóa món</button>
                        </div>

                        <div class="grid" style="grid-template-columns: 1.5fr 1.2fr 0.8fr; gap: 10px;">
                            <div class="form-group" style="margin: 0;">
                                <label class="form-label">Sản phẩm</label>
                                <select class="form-control js-product-select" data-field="san_pham_id" required>
                                    <option value="">Chọn sản phẩm</option>
                                    @foreach($availableProducts ?? [] as $product)
                                        <option value="{{ $product->id }}" {{ (string) old('items.0.san_pham_id') === (string) $product->id ? 'selected' : '' }}>
                                            {{ $product->ten_san_pham }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group" style="margin: 0;">
                                <label class="form-label">Kích cỡ</label>
                                <select class="form-control js-size-select" data-field="kich_co_id">
                                    <option value="">Không chọn kích cỡ</option>
                                </select>
                            </div>

                            <div class="form-group" style="margin: 0;">
                                <label class="form-label">Số lượng</label>
                                <input type="number" class="form-control" data-field="so_luong" min="1" step="1" value="{{ old('items.0.so_luong', 1) }}" required>
                            </div>
                        </div>

                        <div class="form-group" style="margin-top: 8px; margin-bottom: 0;">
                            <label class="form-label">Ghi chú món</label>
                            <input type="text" class="form-control" data-field="ghi_chu_mon" maxlength="255" value="{{ old('items.0.ghi_chu_mon') }}" placeholder="Ít đá, ít ngọt...">
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('create-order-modal')">Hủy</button>
            <button class="btn btn-primary" onclick="document.getElementById('create-order-form').submit()">Lưu đơn hàng</button>
        </div>
    </div>
</div>

{{-- Orders table --}}
<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Mã đơn</th>
                    <th>Khách hàng</th>
                    <th>Bàn / Loại</th>
                    <th>Tổng tiền</th>
                    <th>Trạng thái</th>
                    <th>Thanh toán</th>
                    <th>Thời gian</th>
                    <th class="col-action-xl">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $badgeMap = [
                        'chờ xác nhận' => 'badge-pending',
                        'đã xác nhận'  => 'badge-done',
                        'đã hủy'       => 'badge-cancelled',
                    ];
                    $statusLabels = [
                        'chờ xác nhận' => 'Chờ xác nhận',
                        'đã xác nhận'  => 'Đã xác nhận',
                        'đã hủy'       => 'Đã hủy',
                    ];
                @endphp
                @forelse($orders ?? [] as $order)
                <tr>
                    <td><span class="font-700">#{{ $order->id }}</span><br><span class="text-11 text-muted">{{ $order->ma_don_hang }}</span></td>
                    <td>
                        <div class="font-600">{{ $order->nguoiDung->ho_ten ?? 'Khách vãng lai' }}</div>
                        <div class="text-12 text-muted">{{ $order->nguoiDung->so_dien_thoai ?? '' }}</div>
                    </td>
                    <td>
                        @if($order->ban_an_id)
                            Bàn {{ $order->banAn->so_ban ?? '?' }}
                        @else
                            <span class="text-muted">Online</span>
                        @endif
                    </td>
                    <td class="price-text">
                        {{ number_format($order->tong_tien, 0, ',', '.') }}đ
                    </td>
                    <td>
                        <span class="badge {{ $badgeMap[$order->trang_thai_don] ?? 'badge-default' }}">
                            {{ $statusLabels[$order->trang_thai_don] ?? $order->trang_thai_don }}
                        </span>
                    </td>
                    <td>
                        <span class="badge {{ $order->trang_thai_thanh_toan === 'đã thanh toán' ? 'badge-done' : 'badge-pending' }}">
                            {{ $order->trang_thai_thanh_toan === 'đã thanh toán' ? 'Đã TT' : 'Chưa TT' }}
                        </span>
                    </td>
                    <td class="text-12 text-muted">
                        {{ $order->created_at->format('d/m H:i') }}
                    </td>
                    <td>
                        <div class="action-row">
                            <a href="{{ route('manager.orders.show', $order->id) }}" class="btn btn-primary btn-sm">Chi tiết</a>
                            @if($order->trang_thai_don === 'chờ xác nhận')
                            <form method="POST" action="{{ route('manager.orders.status', $order->id) }}"
                                  onsubmit="return confirm('Xác nhận đơn #{{ $order->id }}?')">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="trang_thai" value="đã xác nhận">
                                <button type="submit" class="btn btn-secondary btn-sm">Xác nhận</button>
                            </form>
                            <form method="POST" action="{{ route('manager.orders.status', $order->id) }}"
                                  onsubmit="return confirm('Hủy đơn #{{ $order->id }}?')">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="trang_thai" value="đã hủy">
                                <button type="submit" class="btn btn-danger btn-sm">Hủy</button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="empty-state">
                        Không có đơn hàng nào phù hợp với bộ lọc.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if(isset($orders) && method_exists($orders, 'hasPages') && $orders->hasPages())
    <div class="card-footer">
        <div class="pagination-footer">
            <span class="text-sm text-muted">
                Hiển thị {{ $orders->firstItem() }}–{{ $orders->lastItem() }} / {{ $orders->total() }} đơn
            </span>
            {{ $orders->appends(request()->query())->links() }}
        </div>
    </div>
    @endif
</div>

@endsection

<script id="order-product-map-data" type="application/json">@json($productSizeMap ?? [])</script>

@push('scripts')
<script>
    const orderProductMap = JSON.parse(
        document.getElementById('order-product-map-data')?.textContent || '{}'
    );
    const shouldOpenCreateOrderModal =
        document.getElementById('create-order-modal')?.dataset.autoOpen === '1';

    function updateOrderTypeFields() {
        const typeSelect = document.getElementById('create-order-type');
        const tableSelect = document.getElementById('create-order-table');
        const addressGroup = document.getElementById('create-order-address-group');
        const isOnline = typeSelect && typeSelect.value === 'đặt online';

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
@endpush
