{{-- Bảng danh sách đơn hàng (phần tự cập nhật qua polling). Cần: $orders (paginator). --}}
<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Bàn</th>
                    <th>Khách hàng</th>
                    <th>Mã đơn</th>
                    <th>Tổng tiền</th>
                    <th class="text-center">Thanh toán</th>
                    <th>Thời gian</th>
                    <th class="col-action-xl">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders ?? [] as $order)
                    <tr>
                        <td>
                            @if($order->ban_an_id)
                                <span class="font-700">Bàn {{ $order->banAn->so_ban ?? '?' }}</span>
                            @else
                                <span class="text-muted">Online</span>
                            @endif
                        </td>
                        <td>
                            <div class="font-600">{{ $order->nguoiDung?->hoSoKhachHang?->ho_ten ?? $order->nguoiDung?->email ?? 'Khách vãng lai' }}</div>
                            <div class="text-12 text-muted">{{ $order->nguoiDung?->hoSoKhachHang?->so_dien_thoai ?? '' }}</div>
                        </td>
                        <td>
                            <span class="font-700">#{{ $order->id }}</span><br>
                            <span class="text-11 text-muted">{{ $order->ma_don_hang }}</span>
                        </td>
                        <td class="price-text">
                            {{ number_format($order->tong_tien, 0, ',', '.') }}đ
                        </td>
                        <td class="text-center">
                            <span
                                class="badge {{ $order->trang_thai_thanh_toan === 'đã thanh toán' ? 'badge-done' : 'badge-pending' }}" style="min-width: 115px; justify-content: center;">
                                {{ $order->trang_thai_thanh_toan === 'đã thanh toán' ? 'Đã thanh toán' : 'Chưa thanh toán' }}
                            </span>
                        </td>
                        <td class="text-12 text-muted">
                            {{ $order->created_at->format('d/m H:i') }}
                        </td>
                        <td>
                            <div class="action-row">
                                @if($order->trang_thai_thanh_toan !== 'chưa thanh toán')
                                    <a href="{{ route('manager.orders.show', $order->id) }}" class="btn btn-primary btn-sm">Chi tiết</a>
                                @else
                                    <a href="{{ route('manager.orders.edit', $order->id) }}" class="btn btn-secondary btn-sm">Sửa</a>
                                @endif
                                <form action="{{ route('manager.orders.destroy', $order->id) }}" method="POST"
                                    onsubmit="return confirmDelete(this, 'Bạn có chắc chắn muốn xóa đơn hàng này? Việc này sẽ hoàn lại số lượng nguyên liệu trong kho và cập nhật bàn.');"
                                    style="display:inline-block;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">Xóa</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="empty-state">
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
