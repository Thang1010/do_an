@extends('manager.layout.app')

@section('title', 'Quản lý nguyên liệu')
@section('breadcrumb', 'Kho & Tài chính / <strong>Quản lý nguyên liệu</strong>')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Quản lý nguyên liệu</h1>
        <p class="page-subtitle">
            {{ $isStoreOwner ? 'Quản lý toàn bộ danh mục nguyên liệu và quyền duyệt yêu cầu.' : 'Xem danh mục và gửi yêu cầu thêm nguyên liệu cho chủ cửa hàng xác nhận.' }}
        </p>
    </div>
    <div class="page-actions">
        <a href="{{ route('manager.ingredients.create') }}" class="btn btn-primary">
            {{ $isStoreOwner ? 'Thêm nguyên liệu' : 'Gửi yêu cầu thêm nguyên liệu' }}
        </a>
    </div>
</div>

<div class="card mb-20">
    <div class="card-body">
        <form method="GET" action="{{ route('manager.ingredients.index') }}" class="filter-bar mb-0">
            <input type="text" name="search" value="{{ $search }}" class="form-control filter-search" placeholder="Tìm nguyên liệu...">
            
            <select name="muc_dich_su_dung" class="form-control" style="width: auto;">
                <option value="">-- Tất cả mục đích --</option>
                @foreach($purposeOptions as $opt)
                    <option value="{{ $opt }}" {{ $purpose === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                @endforeach
            </select>

            <button type="submit" class="btn btn-primary">Lọc</button>
            <a href="{{ route('manager.ingredients.index') }}" class="btn btn-secondary">Xóa lọc</a>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th class="col-stt">STT</th>
                    <th>Tên nguyên liệu</th>
                    <th>Đơn vị tính</th>
                    <th>Mục đích sử dụng</th>
                    <th>Ngày tạo</th>
                    <th class="col-action-lg">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($ingredients as $index => $ingredient)
                <tr>
                    <td>{{ ($ingredients->firstItem() ?? 1) + $index }}</td>
                    <td><span class="font-600">{{ $ingredient->ten_nguyen_lieu }}</span></td>
                    <td>{{ $ingredient->don_vi_tinh }}</td>
                    <td>{{ $ingredient->muc_dich_su_dung ?: '—' }}</td>
                    <td class="text-12 text-muted">{{ optional($ingredient->created_at)->format('d/m/Y') ?? '—' }}</td>
                    <td>
                        <div class="action-row">
                            @if($isStoreOwner)
                                <a href="{{ route('manager.ingredients.edit', ['id' => $ingredient->id]) }}" class="btn btn-warning btn-sm">Sửa</a>
                                <form method="POST" action="{{ route('manager.ingredients.destroy', ['id' => $ingredient->id]) }}" onsubmit="return confirmDelete(this, 'Xóa nguyên liệu {{ addslashes($ingredient->ten_nguyen_lieu) }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">Xóa</button>
                                </form>
                            @else
                                <span class="text-12 text-muted">Chỉ chủ cửa hàng được sửa/xóa</span>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="empty-state">Chưa có nguyên liệu nào trong danh mục.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($ingredients->hasPages())
    <div class="card-footer">
        <div class="pagination-footer">
            <span class="pagination-info">Hiển thị {{ $ingredients->firstItem() }}-{{ $ingredients->lastItem() }} / {{ $ingredients->total() }} nguyên liệu</span>
            {{ $ingredients->links() }}
        </div>
    </div>
    @endif
</div>
@endsection
