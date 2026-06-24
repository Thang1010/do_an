@extends('manager.layout.app')

@section('title', 'Quản lý bàn ăn')
@section('breadcrumb', 'Kinh doanh / <strong>Quản lý bàn ăn</strong>')

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">Quản lý bàn ăn</h1>
        <p class="page-subtitle">Quản lý số bàn phục vụ cho gọi món và đặt bàn</p>
    </div>
    <div class="page-actions">
        <button type="button" class="btn btn-secondary" onclick="openModal('qr-print-modal')">In QR gọi món</button>
        <button class="btn btn-primary" onclick="openModal('create-table-modal')">Thêm bàn ăn</button>
    </div>
</div>

<div class="filter-bar">
    <form method="GET" action="{{ route('manager.tables.index') }}" class="flex-gap-10">
        <input type="text" name="search" class="form-control filter-search"
               placeholder="Tìm số bàn..." value="{{ request('search') }}">
        <select name="trang_thai" class="form-control">
            <option value="">Tất cả trạng thái</option>
            <option value="trong" {{ request('trang_thai') === 'trong' ? 'selected' : '' }}>Trống</option>
            <option value="dang_phuc_vu" {{ request('trang_thai') === 'dang_phuc_vu' ? 'selected' : '' }}>Đang phục vụ</option>
            <option value="da_dat" {{ request('trang_thai') === 'da_dat' ? 'selected' : '' }}>Đã đặt</option>
            <option value="ngung_su_dung" {{ request('trang_thai') === 'ngung_su_dung' ? 'selected' : '' }}>Ngưng sử dụng</option>
        </select>
        <button type="submit" class="btn btn-primary">Lọc</button>
        <a href="{{ route('manager.tables.index') }}" class="btn btn-secondary">Xóa lọc</a>
    </form>
</div>

<div id="tables-grid-wrap">
    @include('manager.tables.partials.grid')
</div>

<div class="modal-backdrop" id="create-table-modal">
    <div class="modal-box modal-md">
        <div class="modal-header">
            <span class="modal-title">Thêm bàn ăn mới</span>
            <button class="modal-close" onclick="closeModal('create-table-modal')">&#x2715;</button>
        </div>
        <div class="modal-body">
            <form id="create-table-form" method="POST" action="{{ route('manager.tables.store') }}">
                @csrf
                <div class="form-group">
                    <label class="form-label">Số bàn <span>*</span></label>
                    <input type="text" name="so_ban" class="form-control" maxlength="20" required>
                </div>
                <div class="form-group mb-0">
                    <label class="form-label">Trạng thái</label>
                    <select name="trang_thai" class="form-control">
                        <option value="trong">Trống</option>
                        <option value="dang_phuc_vu">Đang phục vụ</option>
                        <option value="da_dat">Đã đặt</option>
                        <option value="ngung_su_dung">Ngưng sử dụng</option>
                    </select>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('create-table-modal')">Hủy</button>
            <button type="submit" form="create-table-form" class="btn btn-primary">Lưu bàn ăn</button>
        </div>
    </div>
</div>

@foreach($tables ?? [] as $table)
@continue($table->trang_thai === 'đang phục vụ')
<div class="modal-backdrop" id="edit-table-modal-{{ $table->id }}">
    <div class="modal-box modal-md">
        <div class="modal-header">
            <span class="modal-title">Sửa bàn ăn</span>
            <button class="modal-close" onclick="closeModal('edit-table-modal-{{ $table->id }}')">&#x2715;</button>
        </div>
        <div class="modal-body">
            <form id="edit-table-form-{{ $table->id }}" method="POST" action="{{ route('manager.tables.update', $table->id) }}">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label class="form-label">Số bàn <span>*</span></label>
                    <input type="text" name="so_ban" class="form-control" maxlength="20" value="{{ $table->so_ban }}" required>
                </div>
                <div class="form-group mb-0">
                    <label class="form-label">Trạng thái</label>
                    <select name="trang_thai" class="form-control">
                        <option value="trong" {{ $table->trang_thai === 'trống' ? 'selected' : '' }}>Trống</option>
                        <option value="dang_phuc_vu" {{ $table->trang_thai === 'đang phục vụ' ? 'selected' : '' }}>Đang phục vụ</option>
                        <option value="da_dat" {{ $table->trang_thai === 'đã đặt' ? 'selected' : '' }}>Đã đặt</option>
                        <option value="ngung_su_dung" {{ $table->trang_thai === 'ngưng sử dụng' ? 'selected' : '' }}>Ngưng sử dụng</option>
                    </select>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('edit-table-modal-{{ $table->id }}')">Hủy</button>
            <button type="submit" form="edit-table-form-{{ $table->id }}" class="btn btn-primary">Cập nhật</button>
        </div>
    </div>
</div>
@endforeach

{{-- Modal thông báo: bàn đang phục vụ không thể sửa --}}
<div class="modal-backdrop" id="serving-table-modal">
    <div class="modal-box modal-md">
        <div class="modal-header">
            <span class="modal-title">Không thể sửa bàn</span>
            <button class="modal-close" onclick="closeModal('serving-table-modal')">&#x2715;</button>
        </div>
        <div class="modal-body">
            <p style="margin:0; line-height:1.6;">
                Khách đang sử dụng <strong id="serving-table-name">bàn này</strong> nên không thể sửa thông tin bàn.
                Vui lòng trả bàn (sau khi khách thanh toán) rồi mới chỉnh sửa.
            </p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-primary" onclick="closeModal('serving-table-modal')">Đã hiểu</button>
        </div>
    </div>
</div>

{{-- Modal in QR gọi món cho tất cả bàn (ngay trên trang, không mở tab mới) --}}
<div class="modal-backdrop" id="qr-print-modal">
    <div class="modal-box" id="qr-print-box" style="max-width: 1000px; width: calc(100% - 32px);">
        <div class="modal-header">
            <span class="modal-title">In QR gọi món tại bàn ({{ ($qrTables ?? collect())->count() }} bàn)</span>
            <button class="modal-close" onclick="closeModal('qr-print-modal')">&#x2715;</button>
        </div>
        <div class="modal-body" id="qr-print-area">
            @forelse($qrTables ?? [] as $qrTable)
                @if($loop->first)
                <div class="qr-print-grid">
                @endif
                    <div class="qr-print-card">
                        <div class="qr-print-hint">Quét để gọi món</div>
                        <div class="qr-print-table">Bàn {{ $qrTable->so_ban }}</div>
                        <img src="{{ $qrcodes[$qrTable->id] ?? '' }}" alt="QR Bàn {{ $qrTable->so_ban }}">
                        <div class="qr-print-guide">Dùng camera điện thoại quét mã để xem menu &amp; gọi món tại bàn này.</div>
                    </div>
                @if($loop->last)
                </div>
                @endif
            @empty
                <p class="empty-state">Chưa có bàn nào để tạo QR.</p>
            @endforelse
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('qr-print-modal')">Đóng</button>
            <button class="btn btn-primary" onclick="window.print()">In</button>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
    .qr-print-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 16px;
    }
    .qr-print-card {
        background: #fff;
        border: 1px solid #e2d9c8;
        border-radius: 14px;
        padding: 16px;
        text-align: center;
        break-inside: avoid;
        color: #30261c;
    }
    .qr-print-hint { font-size: 13px; color: #7a6555; font-weight: 600; }
    .qr-print-table { font-size: 24px; font-weight: 700; margin: 4px 0 10px; }
    .qr-print-card img { width: 190px; height: 190px; max-width: 100%; }
    .qr-print-guide { font-size: 12px; color: #7a6555; margin-top: 10px; line-height: 1.5; }

    @media print {
        /* Chỉ in nội dung QR trong modal, ẩn mọi thứ còn lại của trang. */
        body * { visibility: hidden !important; }
        #qr-print-modal, #qr-print-modal * { visibility: visible !important; }
        #qr-print-modal {
            position: absolute !important;
            inset: 0 !important;
            display: block !important;
            background: #fff !important;
            padding: 0 !important;
            overflow: visible !important;
        }
        #qr-print-box {
            max-width: none !important;
            width: 100% !important;
            box-shadow: none !important;
            border: 0 !important;
            background: #fff !important;
            backdrop-filter: none !important;
        }
        #qr-print-modal .modal-header,
        #qr-print-modal .modal-footer { display: none !important; }
        #qr-print-area {
            max-height: none !important;
            overflow: visible !important;
            background: #fff !important;
            padding: 0 !important;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    window.openServingTableModal = function (soBan) {
        var nameEl = document.getElementById('serving-table-name');
        if (nameEl && soBan) { nameEl.textContent = 'bàn ' + soBan; }
        if (typeof openModal === 'function') { openModal('serving-table-modal'); }
    };

    // ── Polling sơ đồ bàn: tự cập nhật trạng thái/thanh toán, không cần F5 ──
    (function () {
        var INTERVAL = 12000; // 12 giây
        var inFlight = false;
        var wrap = document.getElementById('tables-grid-wrap');
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
</script>
@endpush
