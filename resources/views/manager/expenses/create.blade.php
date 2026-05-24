@extends('manager.layout.app')

@section('title', 'Thêm chi tiêu')
@section('breadcrumb', 'Kho & Tài chính / Quản lý chi tiêu / <strong>Thêm chi tiêu</strong>')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Thêm chi tiêu</h1>
        <p class="page-subtitle">Ghi nhận khoản chi mới cho ca làm việc.</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('manager.expenses.index', ['ca_lam_viec_id' => $selectedShiftId]) }}" class="btn btn-secondary">Quay lại</a>
    </div>
</div>

<div class="card mb-20">
    <div class="card-header">
        <span class="card-title">Ghi nhận chi tiêu mới</span>
    </div>
    <div class="card-body">
        @if($shiftGroups->isEmpty())
            <div class="alert alert-warning">Chưa có ca làm việc nào để ghi nhận chi tiêu.</div>
        @else
            <form method="POST" action="{{ route('manager.expenses.store') }}">
                @csrf
                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Ca làm việc <span>*</span></label>
                        <select name="ca_lam_viec_id" class="form-control" required>
                            <option value="">-- Chọn ca làm việc --</option>
                            @foreach($shiftGroups as $shift)
                                <option value="{{ $shift->id }}" {{ (string) old('ca_lam_viec_id', $selectedShiftId) === (string) $shift->id ? 'selected' : '' }}>
                                    {{ $shift->ngay_lam }} • {{ $shift->ten_ca }} ({{ $shift->gio_bat_dau }} - {{ $shift->gio_ket_thuc }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nguyên liệu (Mục đích) <span>*</span></label>
                        <select name="nguyen_lieu_id" class="form-control" required>
                            <option value="">Chọn nguyên liệu</option>
                            @foreach($ingredients as $ingredient)
                                <option value="{{ $ingredient->id }}" {{ old('nguyen_lieu_id') == $ingredient->id ? 'selected' : '' }}>
                                    {{ $ingredient->ten_nguyen_lieu }} ({{ $ingredient->muc_dich_su_dung ?: 'Chưa phân loại' }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Số lượng nhập <span>*</span></label>
                        <input type="number" name="so_luong" class="form-control" min="0.01" step="0.01" required value="{{ old('so_luong') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Giá nhập / Sản phẩm <span>*</span></label>
                        <input type="number" name="don_gia" class="form-control" min="0.01" step="0.01" required value="{{ old('don_gia') }}">
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
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label class="form-label">Ghi chú</label>
                    <input type="text" name="ghi_chu" class="form-control" maxlength="500" value="{{ old('ghi_chu') }}">
                </div>

                <button type="submit" class="btn btn-primary">Lưu chi tiêu</button>
            </form>
        @endif
    </div>
</div>
@endsection
