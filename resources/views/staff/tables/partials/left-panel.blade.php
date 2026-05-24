@if(isset($selectedTable))
    <div class="menu-panel">
        <div class="menu-panel__header">
            <a href="{{ route('staff.tables.index') }}" class="btn btn-secondary btn-sm menu-panel__back">← Danh sách bàn</a>
            <div class="menu-panel__heading menu-panel__heading--center">Menu — Bàn {{ $selectedTable->so_ban }}</div>
            <div class="menu-panel__meta">
                <div class="menu-panel__time">{{ now()->locale('vi')->translatedFormat('l, d/m/Y | H:i:s') }}</div>
                @if($currentAttendance && !$currentAttendance->check_out_luc)
                    <span class="shift-badge shift-badge--checkin">Đang làm việc</span>
                @else
                    <span class="shift-badge shift-badge--none">Không có ca</span>
                @endif
            </div>
        </div>

        <div class="menu-layout">
            <div class="menu-sidebar">
                <div class="menu-section-title">Danh mục</div>
                <div class="menu-category-list">
                    @forelse($menuCategories as $category)
                        <a href="{{ route('staff.tables.index', ['table' => $selectedTable->id, 'category' => $category->id]) }}"
                           class="menu-category-item {{ (int) $selectedCategoryId === (int) $category->id ? 'active' : '' }}">
                            {{ $category->ten_danh_muc }}
                        </a>
                    @empty
                        <div class="menu-empty">Chưa có danh mục món ăn.</div>
                    @endforelse
                </div>
            </div>

            <div class="menu-products">
                <div class="menu-section-title">Món ăn</div>
                <div class="menu-product-grid">
                    @forelse($menuProducts as $product)
                        <form method="POST" action="{{ route('staff.tables.add-item', $selectedTable->id) }}"
                              class="menu-product-form">
                            @csrf
                            <input type="hidden" name="san_pham_id" value="{{ $product->id }}">
                            <input type="hidden" name="category_id" value="{{ $selectedCategoryId }}">
                            @if($selectedOrder)
                                <input type="hidden" name="order_id" value="{{ $selectedOrder->id }}">
                            @endif
                            <button type="submit" class="menu-product-card {{ (int) $selectedProductId === (int) $product->id ? 'menu-product-card--active' : '' }}">
                                <img class="menu-product-card__image" src="{{ $product->image_url }}" alt="{{ $product->ten_san_pham }}"
                                     onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode($product->ten_san_pham) }}&background=E2D9C8&color=30261C&size=120'">
                                <div class="menu-product-card__name">{{ $product->ten_san_pham }}</div>
                                <div class="menu-product-card__price">{{ number_format($product->gia_goc, 0, ',', '.') }}đ</div>
                            </button>
                        </form>
                    @empty
                        <div class="detail-empty" style="grid-column: 1/-1;">
                            <div class="detail-empty__text">Chưa có món trong danh mục.</div>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@else
    @if(isset($assignOrder))
        <div style="margin-bottom: 20px; text-align: center; font-weight: bold; background: #fff3cd; color: #856404; padding: 12px; border-radius: 8px; border: 1px solid #ffeeba;">
            Vui lòng chọn một bàn trống để gán cho đơn hàng #{{ $assignOrder->ma_don_hang ?? $assignOrder->id }}
        </div>
    @endif
    <div class="table-grid">
        @forelse($tables as $table)
            @php
                $isSelected = isset($selectedTable) && $selectedTable->id === $table->id;
                $statusClass = match($table->trang_thai) {
                    'đang phục vụ' => 'occupied',
                    'đang chờ duyệt' => 'reserved',
                    'đã đặt' => 'reserved',
                    'ngưng sử dụng' => 'disabled',
                    'trống' => 'empty',
                    default => 'empty',
                };
                $statusLabel = match($table->trang_thai) {
                    'đang phục vụ' => 'Có khách',
                    'đang chờ duyệt' => 'Chờ duyệt',
                    'đã đặt' => 'Đã đặt',
                    'ngưng sử dụng' => 'Ngưng SD',
                    'trống' => 'Bàn trống',
                    default => 'Bàn trống',
                };
                $latestOrder = $table->donHang
                    ->where('trang_thai_don', '!=', 'đã hủy')
                    ->where('trang_thai_thanh_toan', 'chưa thanh toán')
                    ->first();
                $customerName = $latestOrder?->nguoiDung?->ho_ten ?? $latestOrder?->ten_khach_hang ?? null;
                $customerAvatar = $latestOrder?->nguoiDung?->avatar_url ?? null;
                $orderAmount = $latestOrder?->tong_tien ?? 0;
            @endphp
            @if(isset($assignOrder))
                @if(in_array($table->trang_thai, ['trống', 'đang chờ duyệt']))
                    <form method="POST" action="{{ route('staff.tables.assign-order', $table->id) }}" style="margin: 0;">
                        @csrf
                        <input type="hidden" name="order_id" value="{{ $assignOrder->id }}">
                        <button type="submit" class="table-card table-card--{{ $statusClass }} {{ $isSelected ? 'active' : '' }}" style="width: 100%; border: none; text-align: left; background: none; cursor: pointer; display: block; padding: 16px;">
                            <div class="table-card__header">
                                <span class="table-card__number">Bàn {{ $table->so_ban }}</span>
                                <span class="table-card__status table-card__status--{{ $statusClass }}">{{ $statusLabel }}</span>
                            </div>
                            <div class="table-card__amount table-card__amount--zero">
                                —
                            </div>
                        </button>
                    </form>
                @else
                    <div class="table-card table-card--{{ $statusClass }}" style="opacity: 0.6; cursor: not-allowed;">
                        <div class="table-card__header">
                            <span class="table-card__number">Bàn {{ $table->so_ban }}</span>
                            <span class="table-card__status table-card__status--{{ $statusClass }}">{{ $statusLabel }}</span>
                        </div>
                        @if($customerName)
                            <div class="table-card__body">
                                @if($customerAvatar)
                                    <img class="table-card__customer-avatar" src="{{ $customerAvatar }}" alt="">
                                @endif
                                <span class="table-card__customer-name">{{ $customerName }}</span>
                            </div>
                        @endif
                        <div class="table-card__amount {{ $orderAmount <= 0 ? 'table-card__amount--zero' : '' }}">
                            {{ $orderAmount > 0 ? number_format($orderAmount, 0, ',', '.') . 'đ' : '—' }}
                        </div>
                    </div>
                @endif
            @else
                <a href="/staff/tables?table={{ $table->id }}"
                   class="table-card table-card--{{ $statusClass }} {{ $isSelected ? 'active' : '' }}">
                    <div class="table-card__header">
                        <span class="table-card__number">Bàn {{ $table->so_ban }}</span>
                        <span class="table-card__status table-card__status--{{ $statusClass }}">{{ $statusLabel }}</span>
                    </div>
                    @if($customerName)
                        <div class="table-card__body">
                            @if($customerAvatar)
                                <img class="table-card__customer-avatar" src="{{ $customerAvatar }}" alt="">
                            @endif
                            <span class="table-card__customer-name">{{ $customerName }}</span>
                        </div>
                    @endif
                    <div class="table-card__amount {{ $orderAmount <= 0 ? 'table-card__amount--zero' : '' }}">
                        {{ $orderAmount > 0 ? number_format($orderAmount, 0, ',', '.') . 'đ' : '—' }}
                    </div>
                </a>
            @endif
        @empty
            <div class="detail-empty" style="grid-column: 1/-1;">
                <div class="detail-empty__text">Chưa có bàn nào trong hệ thống</div>
            </div>
        @endforelse
    </div>
@endif

