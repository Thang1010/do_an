@if(isset($selectedTable))
    <div class="menu-panel">
        <div class="menu-panel__header">
            <a href="{{ route('staff.tables.index') }}" class="btn btn-secondary btn-sm menu-panel__back">← Danh sách bàn</a>
            <div class="menu-panel__heading menu-panel__heading--center">
                <div style="margin-bottom: 4px;">Menu — Bàn {{ $selectedTable->so_ban }}</div>
                @if($selectedOrder)
                    <div style="font-size: 14px; font-weight: normal;">
                        Thanh toán: 
                        <span style="padding: 2px 8px; border-radius: 4px; font-weight: 600; background: {{ $selectedOrder->trang_thai_thanh_toan === 'đã thanh toán' ? '#ecfdf5' : '#fffbeb' }}; color: {{ $selectedOrder->trang_thai_thanh_toan === 'đã thanh toán' ? '#059669' : '#d97706' }};">
                            {{ $selectedOrder->trang_thai_thanh_toan === 'đã thanh toán' ? 'Đã thanh toán' : 'Chưa thanh toán' }}
                        </span>
                    </div>
                @endif
            </div>
            <div class="menu-panel__meta">
                <div class="menu-panel__time">{{ now()->locale('vi')->translatedFormat('l, d/m/Y | H:i:s') }}</div>
                @if($currentAttendance && !$currentAttendance->check_out_luc)
                    <span class="shift-badge shift-badge--checkin">Đang làm việc</span>
                @else
                    <span class="shift-badge shift-badge--none">Không có ca</span>
                @endif
            </div>
        </div>

        @if($selectedOrder && $selectedOrder->trang_thai_thanh_toan === 'đã thanh toán')
            <div style="padding: 60px 20px; text-align: center; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                <div style="width: 72px; height: 72px; border-radius: 50%; background: #dcfce7; display: flex; align-items: center; justify-content: center; margin-bottom: 20px;">
                    <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                </div>
                <h3 style="font-size: 20px; font-weight: 700; color: #1a0a00; margin-bottom: 12px;">Đơn hàng đã thanh toán</h3>
                <p style="color: #5f544a; font-size: 15px; max-width: 320px; line-height: 1.5;">Bàn này đã hoàn tất thanh toán. Không thể gọi thêm món. Vui lòng ấn <strong>Trả bàn</strong> để có thể phục vụ lượt khách mới.</p>
            </div>
        @else
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
                                  class="menu-product-form"
                                  data-product-name="{{ $product->ten_san_pham }}"
                                  data-nhiet-do="{{ $product->nhiet_do ?? '' }}"
                                  data-sizes="{{ json_encode($product->kichCo->map(fn($kc) => ['id' => $kc->id, 'name' => $kc->ten_kich_co, 'code' => $kc->ma_kich_co ?? '', 'price' => (float)(($product->gia_khuyen_mai > 0 ? $product->gia_khuyen_mai : $product->gia_goc) * ($kc->he_so_gia ?? 1))])->values()) }}"
                                  onsubmit="return handleStaffAddItem(event, this)">
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
        @endif
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
                    'đã đặt' => 'reserved',
                    'ngưng sử dụng' => 'disabled',
                    'trống' => 'empty',
                    'đang chờ duyệt' => 'reserved',
                    default => 'empty',
                };
                $statusLabel = match($table->trang_thai) {
                    'đang phục vụ' => 'Có khách',
                    'đã đặt' => 'Đã đặt',
                    'ngưng sử dụng' => 'Ngưng SD',
                    'trống' => 'Bàn trống',
                    'đang chờ duyệt' => 'Đã đặt',
                    default => 'Bàn trống',
                };
                $latestOrder = $table->donHang
                    ->filter(function($q) use ($table) {
                        if ($q->trang_thai_thanh_toan === 'chưa thanh toán' && $q->nhan_vien_id !== null) {
                            return true;
                        }
                        if ($table->trang_thai === 'đang phục vụ' && $q->trang_thai_thanh_toan === 'đã thanh toán' && Carbon\Carbon::parse($q->created_at)->isToday()) {
                            return true;
                        }
                        return false;
                    })
                    ->sortByDesc('created_at')
                    ->first();
                $customerName = $latestOrder?->nguoiDung?->hoSoKhachHang?->ho_ten ?? $latestOrder?->nguoiDung?->email ?? null;
                $customerAvatar = $latestOrder?->nguoiDung?->avatar_url ?? null;
                $orderAmount = $latestOrder?->tong_tien ?? 0;
            @endphp
            @if(isset($assignOrder))
                @if(in_array($table->trang_thai, ['trống'], true))
                    <form method="POST" action="{{ route('staff.tables.assign-order', $table->id) }}" style="margin: 0;">
                        @csrf
                        <input type="hidden" name="order_id" value="{{ $assignOrder->id }}">
                        <button type="submit" class="table-card table-card--{{ $statusClass }} {{ $isSelected ? 'active' : '' }}" style="width: 100%; border: none; text-align: left; background: none; cursor: pointer; display: block; padding: 16px;">
                            <div class="table-card__header">
                                <span class="table-card__number">Bàn {{ $table->so_ban }}</span>
                                <span class="table-card__status table-card__status--{{ $statusClass }}">{{ $statusLabel }}</span>
                            </div>
                            <div class="table-card__amount table-card__amount--zero" style="display: flex; justify-content: space-between; align-items: center;">
                                <span>—</span>
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
                        <div class="table-card__amount {{ $orderAmount <= 0 ? 'table-card__amount--zero' : '' }}" style="display: flex; justify-content: space-between; align-items: center;">
                            <span>{{ $orderAmount > 0 ? number_format($orderAmount, 0, ',', '.') . 'đ' : '—' }}</span>
                            @if($latestOrder && $orderAmount > 0)
                                <span style="font-size: 11px; padding: 2px 6px; border-radius: 4px; font-weight: 600; background: {{ $latestOrder->trang_thai_thanh_toan === 'đã thanh toán' ? '#ecfdf5' : '#fffbeb' }}; color: {{ $latestOrder->trang_thai_thanh_toan === 'đã thanh toán' ? '#059669' : '#d97706' }};">{{ $latestOrder->trang_thai_thanh_toan === 'đã thanh toán' ? 'Đã TT' : 'Chưa TT' }}</span>
                            @endif
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
                    <div class="table-card__amount {{ $orderAmount <= 0 ? 'table-card__amount--zero' : '' }}" style="display: flex; justify-content: space-between; align-items: center;">
                        <span>{{ $orderAmount > 0 ? number_format($orderAmount, 0, ',', '.') . 'đ' : '—' }}</span>
                        @if($latestOrder && $orderAmount > 0)
                            <span style="font-size: 11px; padding: 2px 6px; border-radius: 4px; font-weight: 600; background: {{ $latestOrder->trang_thai_thanh_toan === 'đã thanh toán' ? '#ecfdf5' : '#fffbeb' }}; color: {{ $latestOrder->trang_thai_thanh_toan === 'đã thanh toán' ? '#059669' : '#d97706' }};">{{ $latestOrder->trang_thai_thanh_toan === 'đã thanh toán' ? 'Đã TT' : 'Chưa TT' }}</span>
                        @endif
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

