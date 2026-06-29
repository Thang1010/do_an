@extends('manager.layout.app')

@section('title', 'Quản lý chi tiêu')
@section('breadcrumb', 'Kho & Tài chính / <strong>Quản lý chi tiêu</strong>')

@section('content')
@php
    $selectedShiftLabel = 'Chưa chọn ca';
    if ($selectedShift) {
        $selectedShiftLabel = sprintf(
            '%s (%s - %s)',
            $selectedShift->ten_ca,
            $selectedShift->gio_bat_dau,
            $selectedShift->gio_ket_thuc
        );
    }
@endphp

<div class="page-header">
    <div>
        <h1 class="page-title">Quản lý chi tiêu</h1>
        <p class="page-subtitle">Theo dõi chi tiêu theo ca làm việc • {{ $selectedShiftLabel }}</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('manager.expenses.create', ['ca_lam_viec_id' => $selectedShiftId]) }}" class="btn btn-primary">Thêm chi tiêu</a>
    </div>
</div>

<div class="card mb-12">
    <div class="card-body">
        @if($shiftGroups->isEmpty())
            <div class="alert alert-warning">Chưa có ca làm việc để quản lý chi tiêu.</div>
        @else
            <form method="GET" action="{{ route('manager.expenses.index') }}" class="filter-bar mb-0">
                <select name="ca_lam_viec_id" class="form-control" style="width: auto;">
                    <option value="">-- Tất cả ca --</option>
                    @foreach($shiftGroupsForFilter as $group)
                        <option value="{{ $group->id }}" {{ (string) $selectedShiftId === (string) $group->id ? 'selected' : '' }}>
                            {{ $group->ngay_lam }} • {{ $group->ten_ca }} ({{ $group->gio_bat_dau }} - {{ $group->gio_ket_thuc }})
                        </option>
                    @endforeach
                </select>
                                <input type="date" name="ngay_lam" class="form-control" value="{{ $filterDate ?? '' }}" style="max-width: 150px;">
                <button type="submit" class="btn btn-primary">Lọc</button>
                <a href="{{ route('manager.expenses.index') }}" class="btn btn-secondary">Xóa lọc</a>
            </form>
            @if(!$selectedShiftId)
                <div class="alert alert-warning mt-10" style="margin-top: 10px;">Không có ca làm việc nào đang diễn ra trong ngày hôm nay. Vui lòng chọn một ca khác để quản lý.</div>
            @endif
        @endif
    </div>
</div>





<div id="expenses-data-wrap">
    @include('manager.expenses.partials.data')
</div>
@endsection

@push('scripts')
<script>
    // ── Polling chi tiêu trong ca: khoản chi mới + tổng kết tự cập nhật, không cần F5 ──
    (function () {
        var wrap = document.getElementById('expenses-data-wrap');
        if (!wrap) return;
        var INTERVAL = 15000; // 15 giây
        var inFlight = false;

        function refresh() {
            if (inFlight || document.hidden) return;
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


