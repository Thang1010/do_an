{{-- Danh sách chi tiêu + tổng kết (tự cập nhật qua polling). Cần: $expenses (paginator), $summary (array). --}}
<div class="card mb-20">
    <div class="card-header">
        <span class="card-title">Danh sách chi tiêu</span>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Thời gian</th>
                    <th>Tên nguyên liệu</th>
                    <th>Mục đích</th>
                    <th>Số lượng</th>
                    <th>Giá nhập / sản phẩm</th>
                    <th>Thành tiền</th>
                    <th>Thanh toán</th>
                    <th>Người ghi nhận</th>
                    <th>Ghi chú</th>
                </tr>
            </thead>
            <tbody>
                @forelse($expenses as $expense)
                    @php
                        $history = $expense->lichSuKho;
                        $quantity = $history?->so_luong;
                        $unitPrice = $history?->gia_nhap;
                        $totalCost = ($quantity !== null && $unitPrice !== null)
                            ? ((float) $quantity * (float) $unitPrice)
                            : null;
                    @endphp
                    <tr>
                        <td class="text-12 text-muted">{{ optional($expense->created_at)->format('d/m/Y H:i') ?? '—' }}</td>
                        <td><strong>{{ optional($expense->nguyenLieu)->ten_nguyen_lieu ?? '—' }}</strong></td>
                        <td>{{ optional($expense->nguyenLieu)->muc_dich_su_dung ?? '—' }}</td>
                        <td>{{ $quantity !== null ? number_format((float) $quantity, 2, ',', '.') : '—' }} {{ optional($expense->nguyenLieu)->don_vi_tinh }}</td>
                        <td class="price-text">{{ $unitPrice !== null ? number_format((float) $unitPrice, 0, ',', '.') . 'đ' : '—' }}</td>
                        <td class="price-text">{{ $totalCost !== null ? number_format($totalCost, 0, ',', '.') . 'đ' : '—' }}</td>
                        <td>{{ $expense->phuong_thuc_thanh_toan }}</td>
                        <td>{{ $expense->nguoiTao->ho_ten ?? '—' }}</td>
                        <td class="text-muted">{{ $expense->ghi_chu ?: '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="empty-state">Chưa có khoản chi nào cho ca này.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('manager.partials.pager', ['paginator' => $expenses, 'label' => 'khoản'])
</div>

<div class="grid-3 mb-20">
    <div class="stat-card">
        <div class="stat-label">Tổng chi (tiền mặt)</div>
        <div class="stat-value" style="font-size: 20px;">{{ number_format($summary['tong_tien_mat'] ?? 0, 0, ',', '.') }}đ</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Tổng chi (chuyển khoản)</div>
        <div class="stat-value" style="font-size: 20px;">{{ number_format($summary['tong_tien_chuyen_khoan'] ?? 0, 0, ',', '.') }}đ</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Tổng chi</div>
        <div class="stat-value" style="font-size: 20px;">{{ number_format($summary['tong_chi'] ?? 0, 0, ',', '.') }}đ</div>
    </div>
</div>
