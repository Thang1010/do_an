{{-- Lưới danh sách bàn (phần tự cập nhật qua polling). Cần: $tables (paginator). --}}
<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th class="col-stt">STT</th>
                    <th>Số bàn</th>
                    <th>Trạng thái</th>
                    <th>Trạng thái thanh toán</th>
                    <th class="col-action-xl">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tables ?? [] as $i => $table)
                @php
                    $stt = method_exists($tables, 'firstItem') && $tables->firstItem()
                        ? ($tables->firstItem() + $i)
                        : ($i + 1);

                    $statusClass = match ($table->trang_thai) {
                        'đang phục vụ' => 'badge-brew',
                        'đã đặt' => 'badge-pending',
                        'ngưng sử dụng' => 'badge-inactive',
                        default => 'badge-active',
                    };

                    // donHang đã được nạp sẵn chỉ gồm đơn khách mới chưa phục vụ (controller).
                    $soMonMoi = $table->donHang->sum(fn($o) => $o->chiTietDonHang->sum('so_luong'));

                    $paymentClass = 'badge-default';
                    $paymentLabel = 'Không có';
                    $showPaymentBadge = false;

                    if (in_array($table->trang_thai, ['đang phục vụ', 'đã đặt'], true)) {
                        $showPaymentBadge = true;

                        if (($table->so_don_chua_thanh_toan ?? 0) > 0) {
                            $paymentClass = 'badge-pending';
                            $paymentLabel = 'Chưa thanh toán';
                        } elseif (($table->so_don_da_thanh_toan ?? 0) > 0) {
                            $paymentClass = 'badge-done';
                            $paymentLabel = 'Đã thanh toán';
                        } else {
                            $paymentClass = 'badge-pending';
                            $paymentLabel = 'Chưa thanh toán';
                        }
                    }
                @endphp
                <tr>
                    <td>{{ $stt }}</td>
                    <td><span class="font-600">{{ $table->so_ban }}</span></td>
                    <td>
                        <span class="badge {{ $statusClass }}">{{ ucfirst($table->trang_thai) }}</span>
                        @if($soMonMoi > 0)
                            <span class="badge badge-new-order">🔔 {{ $soMonMoi }} món mới</span>
                        @endif
                    </td>
                    <td>
                        @if($showPaymentBadge)
                            <span class="badge {{ $paymentClass }}">{{ $paymentLabel }}</span>
                        @else
                            <span class="text-muted">{{ $paymentLabel }}</span>
                        @endif
                    </td>
                    <td>
                        <div class="action-row">
                            <a href="{{ route('manager.tables.show', $table->id) }}" class="btn btn-primary btn-sm">Chi tiết</a>
                            @if($table->trang_thai === 'đang phục vụ')
                                <button type="button" class="btn btn-secondary btn-sm" onclick="openServingTableModal('{{ addslashes($table->so_ban) }}')">Sửa</button>
                            @else
                                <button class="btn btn-secondary btn-sm" onclick="openModal('edit-table-modal-{{ $table->id }}')">Sửa</button>
                            @endif
                            <form method="POST" action="{{ route('manager.tables.destroy', $table->id) }}"
                                  onsubmit="return confirmDelete(this, 'Xóa bàn {{ $table->so_ban }}?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">Xóa</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="empty-state">
                        Chưa có bàn ăn nào. <button class="btn btn-link link-primary" onclick="openModal('create-table-modal')">Thêm ngay</button>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('manager.partials.pager', ['paginator' => $tables, 'label' => 'bàn ăn'])
</div>
