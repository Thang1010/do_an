@extends('staff.layout.app')
@section('title', 'Thêm chi tiêu')
@section('breadcrumb')
Nhân viên / Chi tiêu / <strong>Thêm chi tiêu</strong>
@endsection

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Thêm chi tiêu</h1>
        <p class="page-subtitle">Ghi nhận khoản chi mới cho ca làm việc.</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('staff.expenses.index') }}" class="btn btn-secondary">← Quay lại</a>
    </div>
</div>

<div class="card mb-20">
    <div class="card-header">
        <span class="card-title">Ghi nhận chi tiêu mới</span>
        @if($currentShift)
            <span class="text-12 text-muted">Ca hiện tại: {{ $currentShift->ten_ca }} — {{ optional($currentShift->ngay_lam)->format('d/m/Y') }}</span>
        @endif
    </div>
    <div class="card-body">
        @if($shiftOptions->isEmpty())
            <div class="alert alert-warning">Bạn chưa có ca làm việc để ghi nhận chi tiêu.</div>
        @else
            <form method="POST" action="{{ route('staff.expenses.store') }}">
                @csrf
                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Ca làm việc <span>*</span></label>
                        <select name="ca_lam_viec_id" class="form-control" required>
                            <option value="">-- Chọn ca làm việc --</option>
                            @foreach($shiftOptions as $shift)
                                <option value="{{ $shift->id }}" {{ (string) old('ca_lam_viec_id', $currentShift?->id) === (string) $shift->id ? 'selected' : '' }}>
                                    {{ optional($shift->ngay_lam)->format('d/m/Y') }} • {{ $shift->ten_ca }} ({{ $shift->gio_bat_dau }} - {{ $shift->gio_ket_thuc }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nguyên liệu <span>*</span></label>
                        <select name="nguyen_lieu_id" class="form-control" required id="exp-ingredient">
                            <option value="">Chọn nguyên liệu</option>
                            @foreach($ingredients as $nl)
                                <option value="{{ $nl->id }}" {{ (string) old('nguyen_lieu_id') === (string) $nl->id ? 'selected' : '' }}>
                                    {{ $nl->ten_nguyen_lieu }} ({{ $nl->don_vi_tinh }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Số lượng <span>*</span></label>
                        <input type="number" name="so_luong" class="form-control" step="0.01" min="0.01" required value="{{ old('so_luong') }}" placeholder="VD: 1">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Giá nhập / Sản phẩm <span>*</span></label>
                        <input type="number" name="don_gia" class="form-control" step="0.01" min="0" required value="{{ old('don_gia') }}" placeholder="VD: 50000" id="exp-price">
                    </div>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Phương thức thanh toán <span>*</span></label>
                        <select name="phuong_thuc_thanh_toan" class="form-control" required>
                            <option value="">Chọn phương thức</option>
                            <option value="tiền mặt" {{ old('phuong_thuc_thanh_toan') === 'tiền mặt' ? 'selected' : '' }}>Tiền mặt</option>
                            <option value="chuyển khoản" {{ old('phuong_thuc_thanh_toan') === 'chuyển khoản' ? 'selected' : '' }}>Chuyển khoản</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Ghi chú</label>
                        <input type="text" name="ghi_chu" class="form-control" maxlength="500" value="{{ old('ghi_chu') }}" placeholder="VD: Mua thêm sữa">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Ghi nhận chi tiêu</button>
            </form>
        @endif
    </div>
</div>
@endsection

