@extends('staff.layout.app')
@section('title', 'Đơn mang về')
@section('breadcrumb')
    Nhân viên / <strong>Đơn mang về</strong>
@endsection

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Đơn mang về</h1>
            <p class="page-subtitle">Hàng đợi đơn online đã thanh toán — pha chế, đóng gói rồi bấm "Đã giao"</p>
        </div>
        <div class="page-actions">
            <a href="/staff/takeaway" class="btn btn-secondary">Làm mới</a>
        </div>
    </div>

    {{-- ── Hàng đợi cần xử lý ── --}}
    <div class="card" style="margin-bottom: 20px;">
        <div class="card-header">
            <span class="card-title">Cần xử lý</span>
            <span class="badge badge-pending">{{ $orders->count() }} đơn</span>
        </div>
        <div class="card-body">
            @if($orders->isEmpty())
                <div class="empty-state" style="padding: 32px; text-align: center;">
                    Không có đơn mang về nào đang chờ.
                </div>
            @else
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 16px;">
                    @foreach($orders as $order)
                        <div style="border: 1px solid rgba(234,88,12,0.35); border-radius: 14px; padding: 16px; background: rgba(234,88,12,0.04); display: flex; flex-direction: column; gap: 10px;">
                            <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px;">
                                <span class="font-600">{{ $order->ma_don_hang ?? '#' . $order->id }}</span>
                                <span style="background:#ea580c;color:#fff;border-radius:999px;padding:2px 10px;font-size:12px;font-weight:700;">MANG VỀ</span>
                            </div>

                            <div class="text-12 text-muted">
                                {{ optional($order->created_at)->format('d/m/Y H:i') }}
                                · {{ $order->nguoiDung?->hoSoKhachHang?->ho_ten ?? $order->nguoiDung?->email ?? $order->email_khach_hang ?? 'Khách lẻ' }}
                            </div>

                            <div style="border-top: 1px dashed rgba(0,0,0,0.08); padding-top: 8px; display: flex; flex-direction: column; gap: 4px;">
                                @foreach($order->chiTietDonHang as $item)
                                    <div style="display: flex; justify-content: space-between; gap: 8px; font-size: 14px;">
                                        <span><span class="font-600">{{ $item->so_luong }}×</span> {{ $item->ten_san_pham }}@if($item->ten_kich_co) <span class="text-muted">({{ $item->ten_kich_co }})</span>@endif</span>
                                    </div>
                                    @if($item->ghi_chu_mon)
                                        <div class="text-12" style="color:#b45309; padding-left: 6px;">{{ $item->ghi_chu_mon }}</div>
                                    @endif
                                @endforeach
                            </div>

                            <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px; border-top: 1px dashed rgba(0,0,0,0.08); padding-top: 8px;">
                                <span class="price-text font-600">{{ number_format($order->tong_tien ?? 0, 0, ',', '.') }}đ</span>
                                <span class="badge badge-done">Đã thanh toán</span>
                            </div>

                            <div style="display: flex; gap: 8px;">
                                <a href="/staff/orders/{{ $order->id }}" class="btn btn-secondary btn-sm" style="flex: 1; justify-content: center;">Chi tiết</a>
                                <form method="POST" action="{{ route('staff.takeaway.delivered', $order->id) }}" style="flex: 1;"
                                    onsubmit="return confirmDelivered(this, '{{ $order->ma_don_hang ?? ('#' . $order->id) }}')">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="btn btn-primary btn-sm" style="width: 100%; justify-content: center;">Đã giao</button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- ── Đã giao hôm nay ── --}}
    @if($deliveredToday->isNotEmpty())
        <div class="card">
            <div class="card-header">
                <span class="card-title">Đã giao hôm nay</span>
                <span class="badge badge-default">{{ $deliveredToday->count() }} đơn</span>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Mã đơn</th>
                            <th>Khách hàng</th>
                            <th>Tổng tiền</th>
                            <th>Giao lúc</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($deliveredToday as $order)
                            <tr>
                                <td class="font-600">{{ $order->ma_don_hang ?? '#' . $order->id }}</td>
                                <td>{{ $order->nguoiDung?->hoSoKhachHang?->ho_ten ?? $order->nguoiDung?->email ?? $order->email_khach_hang ?? '—' }}</td>
                                <td class="price-text">{{ number_format($order->tong_tien ?? 0, 0, ',', '.') }}đ</td>
                                <td class="text-muted text-12">{{ optional($order->da_giao_luc)->format('H:i') }}</td>
                                <td><a href="/staff/orders/{{ $order->id }}" class="btn btn-secondary btn-sm">Chi tiết</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- ── Modal xác nhận đã giao ── --}}
    <div id="delivered-confirm-modal" class="force-password-modal" style="display:none; z-index:10001;" role="dialog" aria-modal="true">
        <div id="delivered-confirm-backdrop" class="force-password-modal__backdrop"></div>
        <div class="force-password-modal__panel" style="width: min(400px, 92vw);">
            <div class="force-password-modal__title">Xác nhận đã giao</div>
            <p class="force-password-modal__desc" style="margin-bottom: 28px;">
                Xác nhận đã giao đơn mang về <strong id="delivered-confirm-code"></strong> cho khách?
            </p>
            <div style="display: flex; gap: 12px; justify-content: center;">
                <button id="delivered-confirm-cancel" type="button" class="force-password-modal__submit"
                    style="background: transparent; border: 1px solid rgba(241, 240, 238, 0.4); color: rgba(241, 240, 238, 0.8);">
                    Hủy
                </button>
                <button id="delivered-confirm-ok" type="button" class="force-password-modal__submit"
                    style="background: #c49a6c; color: #1a120c;">
                    Xác nhận
                </button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    (function () {
        var modal = document.getElementById('delivered-confirm-modal');
        var backdrop = document.getElementById('delivered-confirm-backdrop');
        var okBtn = document.getElementById('delivered-confirm-ok');
        var cancelBtn = document.getElementById('delivered-confirm-cancel');
        var codeEl = document.getElementById('delivered-confirm-code');
        var pendingForm = null;

        function closeModal() {
            modal.style.display = 'none';
            document.body.style.overflow = '';
            pendingForm = null;
        }

        window.confirmDelivered = function (form, code) {
            pendingForm = form;
            if (codeEl) codeEl.textContent = code || '';
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            return false; // chặn submit, chờ xác nhận trong modal
        };

        if (okBtn) okBtn.addEventListener('click', function () {
            var f = pendingForm;
            closeModal();
            if (f) f.submit();
        });
        if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
        if (backdrop) backdrop.addEventListener('click', closeModal);
    })();
</script>
@endpush
