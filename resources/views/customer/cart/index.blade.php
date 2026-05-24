@extends('customer.layout.app')

@section('title', 'Giỏ hàng - XM Coffee')
@section('meta_description', 'Xem và đặt hàng từ giỏ hàng của bạn tại XM Coffee.')

@section('header_overlay', 'bg-black/30')
@section('body_class', 'cart-page')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/cart.css') }}">
@endpush

@section('content')
    {{-- ============ MAIN ============ --}}
    <main class="cart-main">
        <div class="cart-container">

            @if(session('error') || $errors->any())
                <div class="mb-6" style="background: rgba(185, 28, 28, 0.12); border: 1px solid rgba(185, 28, 28, 0.4); color: #fca5a5; padding: 12px 16px; border-radius: 12px;">
                    <div style="font-weight: 600; margin-bottom: 6px;">Không thể đặt hàng</div>
                    @if(session('error'))
                        <div style="margin-bottom: 4px;">{{ session('error') }}</div>
                    @endif
                    @if($errors->any())
                        @foreach($errors->all() as $error)
                            <div style="margin-bottom: 2px;">• {{ $error }}</div>
                        @endforeach
                    @endif
                </div>
            @endif
            @if(session('success'))
                <div class="mb-6" style="background: rgba(16, 185, 129, 0.12); border: 1px solid rgba(16, 185, 129, 0.4); color: #6ee7b7; padding: 12px 16px; border-radius: 12px; margin-bottom: 24px;">
                    <div style="font-weight: 600;">{{ session('success') }}</div>
                </div>
            @endif

            @if(!empty($items))
                @auth
                    @if(auth()->user()->isKhachHang())
                        {{-- ── Modal Sửa Giỏ Hàng (Chưa đặt) ── --}}
                        <div id="edit-cart-modal" class="cart-order-modal" aria-hidden="true" style="z-index: 1000;">
                            <div class="cart-order-backdrop" onclick="closeEditCartModal()"></div>
                            <div class="cart-order-panel cart-modal-panel cart-modal-panel--wide">
                                <button type="button" class="cart-order-close cart-modal-close" onclick="closeEditCartModal()">x</button>

                                <div class="cart-modal-header cart-modal-header--center">
                                    <span class="cart-modal-spacer" aria-hidden="true"></span>
                                    <h2 class="cart-modal-title">Món ăn chưa đặt</h2>
                                    <a href="{{ route('menu.index') }}" class="cart-modal-action">+ Thêm món</a>
                                </div>

                                {{-- Item list --}}
                                <div id="cart-items-list" style="max-height: 400px; overflow-y: auto; padding-right: 8px;">
                                    @foreach($items as $key => $item)
                                        <div class="cart-item cart-item--stacked" id="item-{{ $key }}" data-key="{{ $key }}" data-price="{{ $item['price'] }}">
                                            <div class="cart-item-row cart-item-row--main">
                                                <div class="cart-item-row cart-item-row--main">
                                                    <div class="cart-item-title-row">
                                                        <div class="cart-item-name">{{ $item['name'] }}</div>
                                                        <span class="cart-item-note-display" data-note-display="{{ $key }}" @if(empty($item['note'])) style="display:none;" @endif>
                                                            {{ $item['note'] ?? '' }}
                                                        </span>
                                                        <button type="button" class="cart-item-note-toggle" data-note-key="{{ $key }}" aria-label="Ghi chú">
                                                            <svg class="cart-item-note-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                                <path d="M12 20h9" />
                                                                <path d="M16.5 3.5a2.121 2.121 0 113 3L7 19l-4 1 1-4 12.5-12.5z" />
                                                            </svg>
                                                        </button>
                                                    </div>
                                                    @if(!empty($item['size_name']))
                                                        <div class="cart-item-size">Size: {{ $item['size_name'] }}</div>
                                                    @endif
                                                </div>
                                                <div class="cart-item-price">{{ number_format($item['price'], 0, ',', '.') }}đ</div>
                                            </div>

                                            <div class="cart-item-row cart-item-row--controls">
                                                <div class="cart-item-qty-wrap">
                                                    <button type="button" class="qty-btn qty-dec" data-key="{{ $key }}">−</button>
                                                    <span class="qty-val" id="qty-{{ $key }}">{{ $item['qty'] }}</span>
                                                    <button type="button" class="qty-btn qty-inc" data-key="{{ $key }}">+</button>
                                                </div>
                                                <div class="cart-item-note-wrap hidden" data-note-wrap="{{ $key }}">
                                                    <input type="text" class="cart-item-note-input" value="{{ $item['note'] ?? '' }}" placeholder="Ghi chú món..." data-note-input="{{ $key }}">
                                                    <button type="button" class="cart-item-note-save" data-note-key="{{ $key }}" data-note-scope="cart">Lưu</button>
                                                </div>
                                            </div>
                                            <div class="cart-item-subtotal hidden" id="sub-{{ $key }}" style="display:none;">{{ number_format($item['subtotal'], 0, ',', '.') }}đ</div>
                                            <button class="cart-item-remove hidden" data-key="{{ $key }}" style="display:none;">X</button>
                                        </div>
                                    @endforeach
                                </div>

                        @php
                            // Tải voucher chưa dùng, active, trong thời gian hiệu lực
                            $myVouchers = auth()->user()->voucherNguoiDung()
                                ->where('trang_thai', 'chưa dùng')
                                ->with('voucher')
                                ->get()
                                ->filter(function ($v) {
                                    if (!$v->voucher) return false;
                                    if ($v->voucher->trang_thai !== 'hoạt động') return false;
                                    return now()->between($v->voucher->ngay_bat_dau, $v->voucher->ngay_ket_thuc);
                                });
                        @endphp
                        <div class="cart-field-wrap" style="margin-top: 16px;">
                            <label class="cart-field-label" style="color: #d1d5db; font-weight: 600; margin-bottom: 6px; display: block;">Chọn Voucher (Nếu có)</label>
                            @if($myVouchers->isEmpty())
                                <select id="voucher-select" class="cart-select" style="background: rgba(255,255,255,0.05); color: #fff; border: 1px solid rgba(255,255,255,0.1); width: 100%; padding: 10px; border-radius: 6px;" disabled>
                                    <option value="">Bạn chưa có voucher nào khả dụng</option>
                                </select>
                            @else
                                <select id="voucher-select" class="cart-select" style="background: rgba(255,255,255,0.05); color: #fff; border: 1px solid rgba(255,255,255,0.1); width: 100%; padding: 10px; border-radius: 6px;">
                                    <option value="">— Không dùng voucher —</option>
                                    @foreach($myVouchers as $uv)
                                        <option value="{{ $uv->id }}"
                                            data-type="{{ $uv->voucher->loai_giam }}"
                                            data-value="{{ $uv->voucher->gia_tri_giam }}"
                                            data-min="{{ $uv->voucher->don_toi_thieu }}"
                                            data-max="{{ $uv->voucher->giam_toi_da ?? 0 }}">
                                            {{ $uv->voucher->ma_voucher }} — {{ $uv->voucher->ten_voucher }}
                                            @if($uv->voucher->loai_giam === 'phan_tram')
                                                ({{ $uv->voucher->gia_tri_giam }}%
                                                @if($uv->voucher->giam_toi_da) tối đa {{ number_format($uv->voucher->giam_toi_da,0,',','.') }}đ @endif)
                                            @else
                                                (−{{ number_format($uv->voucher->gia_tri_giam,0,',','.') }}đ)
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                <div id="voucher-applied" class="cart-voucher-applied hidden" style="margin-top: 8px; color: white;">
                                    <span>Giảm: </span><span id="voucher-discount-text" style="color: #34d399; font-weight: 600;">0đ</span>
                                </div>
                            @endif
                        </div>

                                <div style="margin-top:20px;text-align:right;">
                                    <span style="font-size:18px;font-weight:bold;color:white;">Tạm tính: <span id="modal-edit-total" style="color: #facc15;">{{ number_format($total, 0, ',', '.') }}đ</span></span>
                                </div>
                                <div class="cart-order-open-wrap" style="margin-top: 16px;">
                                    <button type="button" class="cart-order-open-btn" onclick="closeEditCartModal(); openOrderModal();" style="width: 100%; font-size: 1rem; padding: 12px; background: #059669; color: white; border: none; border-radius: 8px; cursor: pointer;">Đặt hàng</button>
                                </div>
                            </div>
                        </div>
                    @endif
                @endauth

                @guest
                    <div class="cart-items-panel" style="margin-bottom: 24px;">
                        <div class="cart-items-header">
                            <div class="cart-items-title-group">
                                <h2 class="cart-items-title">Món ăn chưa đặt</h2>
                                <p class="cart-items-count">{{ array_sum(array_column($items, 'qty')) }} sản phẩm</p>
                            </div>
                            <a href="{{ route('menu.index') }}" class="cart-continue-link">+ Thêm món</a>
                        </div>
                        <div id="cart-items-list-inline" style="max-height: 440px; overflow-y: auto;">
                            @foreach($items as $key => $item)
                                <div class="cart-item cart-item--stacked" id="item-{{ $key }}" data-key="{{ $key }}" data-price="{{ $item['price'] }}">
                                    <div class="cart-item-row cart-item-row--main">
                                        <div class="cart-item-info">
                                            <div class="cart-item-title-row">
                                                <div class="cart-item-name">{{ $item['name'] }}</div>
                                                <span class="cart-item-note-display" data-note-display="{{ $key }}" @if(empty($item['note'])) style="display:none;" @endif>
                                                    {{ $item['note'] ?? '' }}
                                                </span>
                                                <button type="button" class="cart-item-note-toggle" data-note-key="{{ $key }}" aria-label="Ghi chú">
                                                    <svg class="cart-item-note-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                        <path d="M12 20h9" />
                                                        <path d="M16.5 3.5a2.121 2.121 0 113 3L7 19l-4 1 1-4 12.5-12.5z" />
                                                    </svg>
                                                </button>
                                            </div>
                                            @if(!empty($item['size_name']))
                                                <div class="cart-item-size">Size: {{ $item['size_name'] }}</div>
                                            @endif
                                        </div>
                                        <div class="cart-item-price">{{ number_format($item['price'], 0, ',', '.') }}đ</div>
                                    </div>

                                    <div class="cart-item-row cart-item-row--controls">
                                        <div class="cart-item-qty-wrap">
                                            <button type="button" class="qty-btn qty-dec" data-key="{{ $key }}">−</button>
                                            <span class="qty-val" id="qty-{{ $key }}">{{ $item['qty'] }}</span>
                                            <button type="button" class="qty-btn qty-inc" data-key="{{ $key }}">+</button>
                                        </div>
                                        <div class="cart-item-note-wrap hidden" data-note-wrap="{{ $key }}">
                                            <input type="text" class="cart-item-note-input" value="{{ $item['note'] ?? '' }}" placeholder="Ghi chú món..." data-note-input="{{ $key }}">
                                            <button type="button" class="cart-item-note-save" data-note-key="{{ $key }}" data-note-scope="cart">Lưu</button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="cart-order-open-wrap">
                            <button type="button" class="cart-order-open-btn" onclick="openOrderModal()">Đặt hàng</button>
                        </div>
                    </div>
                @endguest

                <div class="cart-order-modal" id="cart-order-modal" aria-hidden="true">
                    <div class="cart-order-backdrop"></div>
                    <div class="cart-order-panel" role="dialog" aria-modal="true" aria-label="Dat hang">
                        <button type="button" class="cart-order-close" id="close-order-modal" aria-label="Dong">x</button>

                        {{-- ── Checkout panel (Modal) ── --}}
                        <div class="cart-checkout-panel">
                            <h2 class="cart-checkout-title">Đặt hàng</h2>

                            <div class="cart-summary-row">
                                <span>Tạm tính</span>
                                <span id="total-price">{{ number_format($total, 0, ',', '.') }}đ</span>
                            </div>
                            <div class="cart-summary-row" id="voucher-row" style="display:none;">
                                <span>Giảm voucher</span>
                                <span id="voucher-discount-amount" class="text-green-600">−0đ</span>
                            </div>
                            <div class="cart-summary-row cart-summary-total">
                                <span>Tổng cộng</span>
                                <span id="grand-total">{{ number_format($total, 0, ',', '.') }}đ</span>
                            </div>

                            <p class="cart-info-note">Bạn chưa đăng nhập. Vui lòng điền thông tin để đặt hàng.</p>

                            @auth
                                {{-- Radio: Hình thức --}}
                                <div class="cart-field-wrap">
                                    <label class="cart-field-label">Hình thức đặt *</label>
                                    <div class="cart-radio-group">
                                        <label class="cart-radio-label">
                                            <input type="radio" name="loai_don_ui" value="dat_ban" id="rd-dat-ban" checked>
                                            <span>Đặt bàn trước (Hẹn giờ đến)</span>
                                        </label>
                                        <label class="cart-radio-label">
                                            <input type="radio" name="loai_don_ui" value="goi_mon" id="rd-goi-mon">
                                            <span>Gọi món tại bàn (Đã có mặt ở quán)</span>
                                        </label>
                                    </div>
                                </div>
                            @endauth

                            {{-- ── KHÁCH VÃNG LAI ── --}}
                            @guest
                                <form id="guest-checkout-form" method="POST" action="{{ route('cart.checkout') }}">
                                    @csrf
                                    <input type="hidden" name="loai_don_hidden" id="guest-loai-don-hidden" value="goi_mon">

                                    <div class="cart-field-wrap">
                                        <label class="cart-field-label" for="ten_khach">Họ và tên *</label>
                                        <input id="ten_khach" type="text" name="ten_khach_hang" required
                                            placeholder="Nguyễn Văn A" class="cart-input">
                                    </div>

                                    <div class="cart-field-wrap">
                                        <label class="cart-field-label" for="sdt_khach">Số điện thoại *</label>
                                        <input id="sdt_khach" type="tel" name="so_dien_thoai_khach" required
                                            placeholder="09xxxxxxxx" class="cart-input"
                                            pattern="[0-9]{10,11}" title="Nhập số điện thoại 10-11 chữ số">
                                    </div>

                                    {{-- Gọi món tại bàn --}}
                                    <div id="section-goi-mon" class="cart-field-wrap">
                                        <label class="cart-field-label">Bàn bạn đang ngồi *</label>
                                        @if($availableTables->count() > 0)
                                            <select name="ban_an_id_goi_mon" class="cart-select">
                                                <option value="">— Chọn bàn —</option>
                                                @foreach($availableTables as $table)
                                                    <option value="{{ $table->id }}">Bàn {{ $table->so_ban }}</option>
                                                @endforeach
                                            </select>
                                        @else
                                            <p class="cart-field-hint warning">Hết bàn trống, vui lòng gọi nhân viên.</p>
                                        @endif
                                        <label class="cart-field-label mt-3">Phương thức thanh toán *</label>
                                        <select name="phuong_thuc_thanh_toan_goi_mon" class="cart-select">
                                            <option value="chuyển khoản">Chuyển khoản (Mã QR) — bắt buộc</option>
                                        </select>
                                        <p class="cart-field-hint">Khách vãng lai cần thanh toán trước để đặt hàng thành công.</p>
                                    </div>

                                    <button type="submit" class="cart-submit-btn" id="guest-submit-btn">Gửi yêu cầu gọi món</button>
                                </form>

                                <div class="cart-login-hint">
                                    <a href="{{ route('auth.login') }}">Đăng nhập</a> để tích điểm và dùng voucher.
                                </div>
                            @endguest

                            {{-- ── KHÁCH ĐÃ ĐĂNG NHẬP ── --}}
                            @auth
                                <form id="auth-checkout-form" method="POST" action="{{ route('cart.checkout') }}">
                                    @csrf
                                    <input type="hidden" name="loai_don_hidden" id="auth-loai-don-hidden" value="dat_ban">
                                    <input type="hidden" name="voucher_nguoi_dung_id" id="selected-voucher-id" value="">

                                    @if(empty(auth()->user()->so_dien_thoai))
                                        <div class="cart-field-wrap">
                                            <label class="cart-field-label">Số điện thoại *</label>
                                            <input type="tel" name="so_dien_thoai_khach" required
                                                placeholder="09xxxxxxxx" class="cart-input" pattern="[0-9]{10,11}">
                                        </div>
                                    @endif

                                    {{-- Đặt bàn trước (auth) --}}
                                    <div id="auth-section-dat-ban" class="cart-field-wrap">
                                        <label class="cart-field-label">Thời gian đến quán *</label>
                                        <input type="datetime-local" name="thoi_gian_den" class="cart-input"
                                            step="60" min="{{ now()->addMinutes(30)->format('Y-m-d\TH:i') }}">
                                        <p class="cart-field-hint">Thanh toán bằng chuyển khoản (QR) — bắt buộc khi đặt trước</p>
                                        <input type="hidden" name="phuong_thuc_thanh_toan_dat_ban" value="chuyển khoản">
                                        @if($availableTables->count() > 0)
                                            <label class="cart-field-label mt-3">Chọn bàn (không bắt buộc)</label>
                                            <select name="ban_an_id_dat_ban" class="cart-select">
                                                <option value="">— Để quán tự xếp bàn —</option>
                                                @foreach($availableTables as $table)
                                                    <option value="{{ $table->id }}">Bàn {{ $table->so_ban }}</option>
                                                @endforeach
                                            </select>
                                        @endif
                                    </div>

                                    {{-- Gọi món tại bàn (auth) --}}
                                    <div id="auth-section-goi-mon" class="cart-field-wrap hidden">
                                        <label class="cart-field-label">Bàn bạn đang ngồi *</label>
                                        @if($availableTables->count() > 0)
                                            <select name="ban_an_id_goi_mon" class="cart-select">
                                                <option value="">— Chọn bàn —</option>
                                                @foreach($availableTables as $table)
                                                    <option value="{{ $table->id }}">Bàn {{ $table->so_ban }}</option>
                                                @endforeach
                                            </select>
                                        @else
                                            <p class="cart-field-hint warning">Hết bàn trống, vui lòng gọi nhân viên.</p>
                                        @endif
                                        <label class="cart-field-label mt-3">Phương thức thanh toán *</label>
                                        <select name="phuong_thuc_thanh_toan_goi_mon" class="cart-select">
                                            <option value="tiền mặt">Tiền mặt tại bàn</option>
                                            <option value="chuyển khoản">Chuyển khoản (Mã QR)</option>
                                        </select>
                                    </div>

                                    <button type="submit" class="cart-submit-btn" id="auth-submit-btn">Xác nhận đặt bàn trước</button>
                                </form>
                            @endauth
                        </div>
                    </div>
                </div>
            @else
                @guest
                    <div class="cart-items-panel" style="margin-bottom: 24px;">
                        <div class="cart-items-header">
                            <div class="cart-items-title-group">
                                <h2 class="cart-items-title">Món ăn chưa đặt</h2>
                                <p class="cart-items-count">0 sản phẩm</p>
                            </div>
                            <a href="{{ route('menu.index') }}" class="cart-continue-link">+ Thêm món</a>
                        </div>
                        <div class="cart-empty" style="border: none; background: transparent; box-shadow: none; padding: 32px 20px;">
                            <h2>Giỏ hàng của bạn đang trống</h2>
                            <p>Hãy chọn món để bắt đầu đặt hàng nhé!</p>
                            <a href="{{ route('menu.index') }}" class="cart-go-menu-btn" style="margin-top: 12px;">Thêm món</a>
                        </div>
                    </div>
                @endguest
            @endif

            @auth
            {{-- HIỂN THỊ DANH SÁCH ĐƠN HÀNG TRONG NGÀY --}}
            <div class="cart-orders-today" style="margin-top: 40px; margin-bottom: 40px; background: rgba(30, 17, 6, 0.5); border-radius: 24px; padding: 28px 24px; border: 1px solid rgba(255, 255, 255, 0.12); backdrop-filter: blur(14px);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
                    <h2 style="font-family: 'Playfair Display', serif; font-size: 24px; font-weight: 700; color: #fff; margin: 0;">Lịch sử đơn hàng</h2>
                    <form action="{{ route('cart.index') }}" method="GET" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                            <input type="date" name="date" class="cart-input" style="padding: 8px 12px; margin: 0; width: 150px; font-size: 0.875rem; color-scheme: dark;" value="{{ $filterDate ?? today()->toDateString() }}">
                            <button type="submit" class="cart-submit-btn" style="padding: 8px 16px; width: auto; margin: 0; font-size: 0.875rem;">Lọc</button>
                            <a href="{{ route('cart.index') }}" class="cart-submit-btn" style="padding: 8px 16px; width: auto; margin: 0; font-size: 0.875rem; background: rgba(255, 255, 255, 0.14); color: #fff; text-decoration: none;">Xóa lọc</a>
                            <a href="{{ route('menu.index') }}" class="cart-submit-btn" style="padding: 8px 16px; width: auto; margin: 0; font-size: 0.875rem; background: #059669; color: #fff; text-decoration: none; border: 1px solid rgba(5, 150, 105, 0.5);">Thêm đơn hàng</a>
                        </form>
                    </div>

                    @if(!empty($items) || (isset($ordersToday) && $ordersToday->count() > 0))
                        <div style="background: rgba(255, 255, 255, 0.03); border-radius: 12px; overflow: hidden; border: 1px solid rgba(255, 255, 255, 0.08);">
                            <div style="overflow-x: auto;">
                                <table style="width: 100%; border-collapse: collapse; text-align: left; min-width: 600px; white-space: nowrap;">
                                    <thead>
                                        <tr style="background: rgba(255, 255, 255, 0.06); border-bottom: 1px solid rgba(255, 255, 255, 0.08);">
                                            <th style="padding: 14px 16px; font-weight: 600; color: #fff;">Mã đơn</th>
                                            <th style="padding: 14px 16px; font-weight: 600; color: #fff;">Món đã gọi</th>
                                            <th style="padding: 14px 16px; font-weight: 600; color: #fff;">Tổng tiền</th>
                                            <th style="padding: 14px 16px; font-weight: 600; color: #fff;">Trạng thái</th>
                                            <th style="padding: 14px 16px; font-weight: 600; text-align: center; color: #fff;">Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if(!empty($items))
                                            <tr style="border-bottom: 1px solid rgba(255, 255, 255, 0.06); transition: background 0.2s; background: rgba(5, 150, 105, 0.1);">
                                                <td style="padding: 14px 16px; font-weight: 500; color: #F5EFE4;">(Chưa đặt hàng)</td>
                                                <td style="padding: 14px 16px; color: rgba(255, 255, 255, 0.78);" id="table-total-qty">
                                                    <div class="cart-order-items">
                                                        @foreach($items as $item)
                                                            <div class="cart-order-item">
                                                                <span class="cart-order-item-name">{{ $item['name'] }}</span>
                                                                <span class="cart-order-item-qty">x{{ $item['qty'] }}</span>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </td>
                                                <td style="padding: 14px 16px; color: #F0DDB8; font-weight: 700;" id="table-total-price">{{ number_format($total, 0, ',', '.') }}đ</td>
                                                <td style="padding: 14px 16px;">
                                                    <span style="color: #6ee7b7; background: rgba(5, 150, 105, 0.2); border: 1px solid rgba(5, 150, 105, 0.5); padding: 4px 8px; border-radius: 50px; font-size: 0.75rem; font-weight: 600;">Mới chọn</span>
                                                </td>
                                                <td style="padding: 14px 16px; text-align: center;">
                                                    <div style="display: flex; gap: 8px; justify-content: center; align-items: center; white-space: nowrap;">
                                                        <button type="button" onclick="openEditCartModal()" class="cart-submit-btn" style="padding: 6px 14px; margin: 0; width: auto; font-size: 0.8rem; background: rgba(255, 255, 255, 0.14); color: #fff;">Sửa</button>
                                                        <button type="button" onclick="openOrderModal()" class="cart-submit-btn" style="padding: 6px 14px; margin: 0; width: auto; font-size: 0.8rem; background: #059669; color: #fff; border: 1px solid rgba(5, 150, 105, 0.5);">Đặt hàng</button>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endif
                                        @if(isset($ordersToday))
                                        @foreach($ordersToday as $order)
                                            <tr style="border-bottom: 1px solid rgba(255, 255, 255, 0.06); transition: background 0.2s;">
                                                <td style="padding: 14px 16px; font-weight: 500; color: #F5EFE4;">#{{ $order->ma_don_hang ?? $order->id }}</td>
                                                <td style="padding: 14px 16px; color: rgba(255, 255, 255, 0.78);">
                                                    <div class="cart-order-items">
                                                        @foreach($order->chiTietDonHang as $item)
                                                            <div class="cart-order-item">
                                                                <span class="cart-order-item-name">{{ $item->ten_san_pham }}</span>
                                                                <span class="cart-order-item-qty">x{{ $item->so_luong }}</span>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </td>
                                                <td style="padding: 14px 16px; color: #F0DDB8; font-weight: 700;">{{ number_format($order->tong_tien, 0, ',', '.') }}đ</td>
                                                <td style="padding: 14px 16px;">
                                                    @if($order->trang_thai_thanh_toan === 'đã thanh toán')
                                                        <span style="color: #6ee7b7; background: rgba(5, 150, 105, 0.2); border: 1px solid rgba(5, 150, 105, 0.5); padding: 4px 8px; border-radius: 50px; font-size: 0.75rem; font-weight: 600;">Đã thanh toán</span>
                                                    @elseif(in_array($order->trang_thai_don, ['chờ xác nhận', 'cho_xac_nhan']))
                                                        <span style="color: #fcd34d; background: rgba(217, 119, 6, 0.2); border: 1px solid rgba(217, 119, 6, 0.5); padding: 4px 8px; border-radius: 50px; font-size: 0.75rem; font-weight: 600;">Chưa xác nhận</span>
                                                    @elseif(in_array($order->trang_thai_don, ['đã hủy', 'huy']))
                                                        <span style="color: #fca5a5; background: rgba(239, 68, 68, 0.2); border: 1px solid rgba(239, 68, 68, 0.5); padding: 4px 8px; border-radius: 50px; font-size: 0.75rem; font-weight: 600;">Đã hủy</span>
                                                    @else
                                                        <span style="color: #93c5fd; background: rgba(37, 99, 235, 0.2); border: 1px solid rgba(37, 99, 235, 0.5); padding: 4px 8px; border-radius: 50px; font-size: 0.75rem; font-weight: 600;">Đã xác nhận</span>
                                                    @endif
                                                </td>
                                                <td style="padding: 14px 16px; text-align: center;">
                                                    <div style="display: flex; gap: 8px; justify-content: center; align-items: center; white-space: nowrap;">
                                                        @if($order->trang_thai_thanh_toan === 'đã thanh toán')
                                                            <button type="button" onclick="showOrderDetails({{ $order->id }})" class="cart-submit-btn" style="padding: 6px 14px; margin: 0; width: auto; font-size: 0.8rem; background: rgba(255, 255, 255, 0.14); color: #fff;">Xem chi tiết</button>
                                                        @elseif(in_array($order->trang_thai_don, ['chờ xác nhận', 'cho_xac_nhan']))
                                                            <button type="button" onclick="showOrderDetails({{ $order->id }})" class="cart-submit-btn" style="padding: 6px 14px; margin: 0; width: auto; font-size: 0.8rem; background: rgba(255, 255, 255, 0.14); color: #fff;">Xem chi tiết</button>
                                                            <button type="button" onclick="openEditOrderModal({{ $order->id }})" class="cart-submit-btn" style="padding: 6px 14px; margin: 0; width: auto; font-size: 0.8rem; background: rgba(255, 255, 255, 0.14); color: #fff;">Sửa đơn</button>
                                                            <form action="{{ route('customer.orders.cancel', $order->id) }}" method="POST" style="margin: 0;" onsubmit="return confirm('Bạn có chắc muốn hủy đơn này?');">
                                                                @csrf @method('PATCH')
                                                                <button type="submit" class="cart-submit-btn" style="padding: 6px 14px; margin: 0; width: auto; font-size: 0.8rem; background: rgba(201, 64, 64, 0.6); color: #fff; border: 1px solid rgba(201, 64, 64, 0.8);">Hủy đơn</button>
                                                            </form>
                                                        @elseif(in_array($order->trang_thai_don, ['đã hủy', 'huy']))
                                                            <button type="button" onclick="showOrderDetails({{ $order->id }})" class="cart-submit-btn" style="padding: 6px 14px; margin: 0; width: auto; font-size: 0.8rem; background: rgba(255, 255, 255, 0.14); color: #fff;">Xem chi tiết</button>
                                                        @else
                                                            <button type="button" onclick="showOrderDetails({{ $order->id }})" class="cart-submit-btn" style="padding: 6px 14px; margin: 0; width: auto; font-size: 0.8rem; background: rgba(255, 255, 255, 0.14); color: #fff;">Xem chi tiết</button>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- Modal Sửa Đơn Đã Đặt --}}
                        <div class="cart-order-modal" id="edit-order-modal" aria-hidden="true" style="z-index: 1000;">
                            <div class="cart-order-backdrop" onclick="closeEditOrderModal()"></div>
                            <div class="cart-order-panel cart-modal-panel cart-modal-panel--wide">
                                    <button type="button" class="cart-order-close cart-modal-close" onclick="closeEditOrderModal()">x</button>

                                    <div class="cart-modal-header">
                                        <h2 class="cart-modal-title">Cập nhật đơn hàng <span id="modal-edit-order-id"></span></h2>
                                        <form id="form-add-item-to-order" method="POST" action="" onsubmit="return confirm('Thêm món: Đơn hàng sẽ bị hủy và các món sẽ được đưa vào giỏ hàng để bạn chọn thêm món rồi đặt lại. Bạn có chắc chắn?');">
                                            @csrf
                                            <button type="submit" class="cart-modal-action">+ Thêm món</button>
                                        </form>
                                    </div>
                                
                                <form id="form-edit-order" method="POST" action="">
                                    @csrf @method('PUT')
                                    <div id="modal-edit-order-content" style="max-height: 400px; overflow-y: auto; padding-right: 8px;">
                                        {{-- JS sẽ render list input --}}
                                    </div>
                                    
                                    {{-- List Voucher --}}
                                    <div class="cart-field-wrap" style="margin-top: 16px;">
                                        <label class="cart-field-label" style="color: #d1d5db; font-weight: 600; margin-bottom: 6px; display: block;">Chọn Voucher</label>
                                        <select name="voucher_nguoi_dung_id" id="edit-order-voucher" class="cart-select" style="background: rgba(255,255,255,0.05); color: #fff; border: 1px solid rgba(255,255,255,0.1); width: 100%; padding: 10px; border-radius: 6px;">
                                            <option value="">— Không dùng voucher —</option>
                                            @if(isset($myVouchers) && !$myVouchers->isEmpty())
                                                @foreach($myVouchers as $uv)
                                                    <option value="{{ $uv->id }}">
                                                        {{ $uv->voucher->ma_voucher }} — {{ $uv->voucher->ten_voucher }}
                                                    </option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>

                                    <div style="margin-top: 20px;">
                                        <button type="submit" class="cart-submit-btn" style="width: 100%; font-size: 1rem; padding: 12px; background: #059669; color: white;">Cập nhật</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        {{-- Modal Chi tiết Đơn hàng --}}
                        <div class="cart-order-modal" id="order-details-modal" aria-hidden="true" style="z-index: 1000;">
                            <div class="cart-order-backdrop" onclick="closeOrderDetailsModal()"></div>
                            <div class="cart-order-panel cart-modal-panel">
                                <button type="button" class="cart-order-close cart-modal-close" onclick="closeOrderDetailsModal()">x</button>
                                <h2 class="cart-modal-title" style="margin-bottom: 16px;">Chi tiết đơn hàng <span id="modal-order-id"></span></h2>
                                <div id="modal-order-content" style="max-height: 400px; overflow-y: auto; padding-right: 8px;">
                                    {{-- JS sẽ render nội dung vào đây --}}
                                </div>
                                <div class="cart-summary-total" style="margin-top: 16px; border-top: 1px solid rgba(255,255,255,0.12); padding-top: 16px; display: flex; justify-content: space-between; align-items: center;">
                                    <span style="font-weight: 600; color: rgba(255,255,255,0.82);">Tổng cộng</span>
                                    <span id="modal-order-total" style="font-weight: 700; color: #F0DDB8; font-size: 1.125rem;"></span>
                                </div>
                            </div>
                        </div>

                        @php
                            $ordersDataMapped = collect();
                            if (isset($ordersToday)) {
                                $ordersDataMapped = $ordersToday->map(function($o) { 
                                    return [
                                        'id' => $o->id, 
                                        'ma_don' => $o->ma_don_hang ?? $o->id, 
                                        'tong_tien' => number_format($o->tong_tien, 0, ',', '.'),
                                        'ten_ban' => $o->banAn ? $o->banAn->ten_ban : null,
                                        'thoi_gian_xac_nhan' => (!in_array($o->trang_thai_don, ['chờ xác nhận', 'cho_xac_nhan', 'đã hủy', 'huy'])) ? \Carbon\Carbon::parse($o->updated_at)->format('H:i d/m/Y') : null,
                                        'voucher' => $o->voucherNguoiDung && $o->voucherNguoiDung->voucher ? $o->voucherNguoiDung->voucher->ten_voucher . ' (-' . number_format($o->so_tien_giam, 0, ',', '.') . 'đ)' : null,
                                        'voucher_nguoi_dung_id' => $o->voucher_nguoi_dung_id,
                                        'items' => $o->chiTietDonHang->map(function($i) {
                                            return [
                                                'id' => $i->id,
                                                'san_pham_id' => $i->san_pham_id,
                                                'kich_co_id' => $i->kich_co_id,
                                                'name' => $i->ten_san_pham,
                                                'qty' => $i->so_luong,
                                                'raw_price' => $i->don_gia,
                                                'price' => number_format($i->don_gia, 0, ',', '.'),
                                                'subtotal' => number_format($i->thanh_tien, 0, ',', '.'),
                                                'size' => $i->ten_kich_co ?? '',
                                                'note' => $i->ghi_chu_mon ?? ''
                                            ];
                                        })
                                    ]; 
                                })->keyBy('id');
                            }
                        @endphp

                        <script>
                            const ordersData = @json($ordersDataMapped);

                            function showOrderDetails(orderId) {
                                const data = ordersData[orderId];
                                if(!data) return;
                                
                                document.getElementById('modal-order-id').innerText = '#' + data.ma_don;
                                document.getElementById('modal-order-total').innerText = data.tong_tien + 'đ';
                                
                                let html = '';
                                html += `<div style="margin-bottom: 16px; padding-bottom: 16px; border-bottom: 1px solid rgba(255,255,255,0.12);">`;
                                if (data.ten_ban) {
                                    html += `<p style="margin-bottom: 4px; color: rgba(255,255,255,0.8);"><strong>Bàn:</strong> ${data.ten_ban}</p>`;
                                }
                                if (data.thoi_gian_xac_nhan) {
                                    html += `<p style="margin-bottom: 4px; color: rgba(255,255,255,0.8);"><strong>Thời gian xác nhận:</strong> ${data.thoi_gian_xac_nhan}</p>`;
                                }
                                if (data.voucher) {
                                    html += `<p style="margin-bottom: 4px; color: #F0DDB8;"><strong>Voucher:</strong> ${data.voucher}</p>`;
                                }
                                html += `</div>`;

                                data.items.forEach(item => {
                                    html += `
                                        <div style="display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px dashed rgba(255,255,255,0.12);">
                                            <div style="flex: 1; padding-right: 12px;">
                                                <div style="font-weight: 600; color: #F5EFE4; font-size: 0.95rem;">${item.name} <span style="color: rgba(255,255,255,0.6); font-weight: normal;">x${item.qty}</span></div>
                                                ${item.size ? `<div style="font-size: 0.85rem; color: rgba(255,255,255,0.6); margin-top: 2px;">Size: ${item.size}</div>` : ''}
                                                ${item.note ? `<div style="font-size: 0.85rem; color: #F0DDB8; margin-top: 2px;">Ghi chú: ${item.note}</div>` : ''}
                                            </div>
                                            <div style="font-weight: 600; color: #F0DDB8;">
                                                ${item.subtotal}đ
                                            </div>
                                        </div>
                                    `;
                                });
                                document.getElementById('modal-order-content').innerHTML = html;
                                
                                const modal = document.getElementById('order-details-modal');
                                modal.classList.add('open');
                                modal.setAttribute('aria-hidden', 'false');
                            }

                            function closeOrderDetailsModal() {
                                const modal = document.getElementById('order-details-modal');
                                modal.classList.remove('open');
                                modal.setAttribute('aria-hidden', 'true');
                            }

                            function openEditCartModal() {
                                const m = document.getElementById('edit-cart-modal');
                                if(m) {
                                    m.classList.add('open');
                                    m.setAttribute('aria-hidden', 'false');
                                    document.body.classList.add('cart-modal-open');
                                }
                            }
                            
                            function closeEditCartModal() {
                                const m = document.getElementById('edit-cart-modal');
                                if(m) {
                                    m.classList.remove('open');
                                    m.setAttribute('aria-hidden', 'true');
                                    document.body.classList.remove('cart-modal-open');
                                }
                            }

                            function openEditOrderModal(orderId) {
                                const data = ordersData[orderId];
                                if(!data) return;

                                document.getElementById('modal-edit-order-id').innerText = '#' + data.ma_don;
                                document.getElementById('form-edit-order').action = '/customer/orders/' + orderId;
                                document.getElementById('form-add-item-to-order').action = '/customer/orders/' + orderId + '/edit-cart';
                                
                                const voucherSel = document.getElementById('edit-order-voucher');
                                if(voucherSel) {
                                    voucherSel.value = data.voucher_nguoi_dung_id || '';
                                }

                                let html = '';
                                data.items.forEach((item, index) => {
                                    const noteKey = `order-${orderId}-${index}`;
                                    html += `
                                        <div class="cart-item cart-item--stacked">
                                            <input type="hidden" name="items[${index}][san_pham_id]" value="${item.san_pham_id}">
                                            ${item.kich_co_id ? `<input type="hidden" name="items[${index}][kich_co_id]" value="${item.kich_co_id}">` : ''}

                                            <div class="cart-item-row cart-item-row--main">
                                                <div class="cart-item-info">
                                                    <div class="cart-item-title-row">
                                                        <div class="cart-item-name">${item.name}</div>
                                                        <span class="cart-item-note-display" data-note-display="${noteKey}" style="${item.note ? '' : 'display:none;'}">${item.note || ''}</span>
                                                        <button type="button" class="cart-item-note-toggle" data-note-key="${noteKey}" aria-label="Ghi chú">
                                                            <svg class="cart-item-note-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                                <path d="M12 20h9" />
                                                                <path d="M16.5 3.5a2.121 2.121 0 113 3L7 19l-4 1 1-4 12.5-12.5z" />
                                                            </svg>
                                                        </button>
                                                    </div>
                                                    ${item.size ? `<div class="cart-item-size">Size: ${item.size}</div>` : ''}
                                                </div>
                                                <div class="cart-item-price">${item.price}đ</div>
                                            </div>

                                            <div class="cart-item-row cart-item-row--controls">
                                                <div class="cart-item-qty-wrap">
                                                    <button type="button" class="qty-btn" onclick="const i=document.getElementById('edit-qty-${index}'); i.value = Math.max(0, parseInt(i.value) - 1); if(i.value == 0) this.closest('.cart-item').remove();">−</button>
                                                    <input type="number" name="items[${index}][so_luong]" id="edit-qty-${index}" value="${item.qty}" class="cart-item-qty-input" readonly>
                                                    <button type="button" class="qty-btn" onclick="const i=document.getElementById('edit-qty-${index}'); i.value = parseInt(i.value) + 1;">+</button>
                                                </div>
                                                <div class="cart-item-note-wrap hidden" data-note-wrap="${noteKey}">
                                                    <input type="text" name="items[${index}][ghi_chu_mon]" value="${item.note || ''}" placeholder="Ghi chú món..." class="cart-item-note-input" data-note-input="${noteKey}">
                                                    <button type="button" class="cart-item-note-save" data-note-key="${noteKey}" data-note-scope="order">Lưu</button>
                                                </div>
                                            </div>
                                        </div>
                                    `;
                                });
                                document.getElementById('modal-edit-order-content').innerHTML = html;
                                
                                const m = document.getElementById('edit-order-modal');
                                if(m) {
                                    m.classList.add('open');
                                    m.setAttribute('aria-hidden', 'false');
                                    document.body.classList.add('cart-modal-open');
                                }
                            }

                            function closeEditOrderModal() {
                                const m = document.getElementById('edit-order-modal');
                                if(m) {
                                    m.classList.remove('open');
                                    m.setAttribute('aria-hidden', 'true');
                                    document.body.classList.remove('cart-modal-open');
                                }
                            }
                        </script>
                    @else
                        <div class="cart-empty" style="border: none; background: transparent; box-shadow: none; padding: 40px 20px;">
                            <svg class="cart-empty-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <h2>Không có đơn hàng nào trong ngày {{ \Carbon\Carbon::parse($filterDate ?? today())->format('d/m/Y') }}</h2>
                            <p>Hãy xem thực đơn và chọn cho mình một món đồ uống thật ngon nhé!</p>
                            <a href="{{ route('menu.index') }}" class="cart-go-menu-btn" style="margin-top: 12px;">Xem thực đơn</a>
                        </div>
                    @endif
            </div>
            @endauth
        </div>
    </main>

@endsection

@push('scripts')
<script>
const CSRF    = document.querySelector('meta[name="csrf-token"]')?.content || '';
const menuUrl = "{{ route('menu.index') }}";
let   rawTotal  = {{ $total ?? 0 }};
let   discountAmount = 0;

/* ── Helpers ─────────────────────────────────────────────── */
function formatVND(n) {
    return new Intl.NumberFormat('vi-VN').format(Math.round(n)) + 'đ';
}

/* ── recalcTotal ─────────────────────────────────────────── */
function recalcTotal() {
    rawTotal = 0;
    let totalQty = 0;
    document.querySelectorAll('.cart-item').forEach(item => {
        const price = parseFloat(item.dataset.price || 0);
        const key   = item.dataset.key;
        if (!key) return; // ignore edit-order-modal items
        const qty   = parseInt(document.getElementById('qty-' + key)?.textContent || 0);
        rawTotal += price * qty;
        totalQty += qty;
    });
    const tpEl = document.getElementById('total-price');
    const gtEl = document.getElementById('grand-total');
    if (tpEl) tpEl.textContent = formatVND(rawTotal);
    const finalTotal = Math.max(0, rawTotal - discountAmount);
    if (gtEl) gtEl.textContent = formatVND(finalTotal);

    const tblEl = document.getElementById('table-total-price');
    if (tblEl) tblEl.textContent = formatVND(finalTotal);
    const tblEditTotal = document.getElementById('modal-edit-total');
    if (tblEditTotal) tblEditTotal.textContent = formatVND(rawTotal);
    
    const tblQty = document.getElementById('table-total-qty');
    if (tblQty) tblQty.textContent = totalQty;
    
    const countEl = document.querySelector('.cart-items-count');
    if (countEl) countEl.textContent = totalQty + ' sản phẩm';
}

/* ── removeItem ──────────────────────────────────────────── */
function removeItem(key) {
    fetch('/cart/remove', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        body: JSON.stringify({ key }),
    }).then(r => r.json()).then(d => {
        document.getElementById('item-' + key)?.remove();
        recalcTotal();

        const countEl = document.querySelector('.cart-page-title p');
        if (countEl) countEl.textContent = (d.cart_count || 0) + ' sản phẩm';

        // Cập nhật badge nav
        document.querySelectorAll('.cart-count-badge').forEach(el => {
            el.textContent = d.cart_count;
            el.style.display = d.cart_count > 0 ? 'flex' : 'none';
        });

        // Giỏ trống → về menu
        if (d.cart_count === 0) {
            window.location.href = menuUrl;
        }
    }).catch(console.error);
}

/* ── updateQty ───────────────────────────────────────────── */
function updateQty(key, delta) {
    const qtyEl  = document.getElementById('qty-' + key);
    const itemEl = document.getElementById('item-' + key);
    if (!qtyEl || !itemEl) return;

    const price = parseFloat(itemEl.dataset.price || 0);
    let   qty   = parseInt(qtyEl.textContent || 1) + delta;

    // Khi giảm xuống 0 → xoá luôn
    if (qty <= 0) {
        removeItem(key);
        return;
    }

    qtyEl.textContent = qty;
    document.getElementById('sub-' + key).textContent = formatVND(price * qty);
    recalcTotal();

    fetch('/cart/update', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        body: JSON.stringify({ key, qty }),
    }).catch(console.error);
}

/* ── Bind +/− / X buttons ────────────────────────────────── */
document.querySelectorAll('.qty-inc').forEach(b => b.addEventListener('click', () => updateQty(b.dataset.key, 1)));
document.querySelectorAll('.qty-dec').forEach(b => b.addEventListener('click', () => updateQty(b.dataset.key, -1)));
document.querySelectorAll('.cart-item-remove').forEach(b => b.addEventListener('click', () => removeItem(b.dataset.key)));

/* ── Notes ──────────────────────────────────────────────── */
function saveNote(key, note) {
    fetch('/cart/note', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        body: JSON.stringify({ key, note }),
    }).catch(console.error);
}

function setNoteDisplay(key, value) {
    const display = document.querySelector(`[data-note-display="${key}"]`);
    if (!display) return;
    const text = (value || '').trim();
    display.textContent = text;
    display.style.display = text ? 'inline-flex' : 'none';
}

function toggleNoteEditor(key) {
    const wrap = document.querySelector(`[data-note-wrap="${key}"]`);
    const input = document.querySelector(`[data-note-input="${key}"]`);
    const display = document.querySelector(`[data-note-display="${key}"]`);
    if (!wrap) return;
    const isHidden = wrap.classList.contains('hidden');
    wrap.classList.toggle('hidden', !isHidden);
    if (display) display.style.display = isHidden ? 'none' : (display.textContent.trim() ? 'inline-flex' : 'none');
    if (isHidden && input) input.focus();
}

document.addEventListener('click', (event) => {
    const toggle = event.target.closest('.cart-item-note-toggle');
    if (toggle) {
        const key = toggle.getAttribute('data-note-key');
        if (key) toggleNoteEditor(key);
        return;
    }

    const save = event.target.closest('.cart-item-note-save');
    if (save) {
        const key = save.getAttribute('data-note-key');
        if (!key) return;
        const input = document.querySelector(`[data-note-input="${key}"]`);
        if (!input) return;
        const value = input.value.trim();
        if (save.getAttribute('data-note-scope') === 'cart') {
            saveNote(key, value);
        }
        setNoteDisplay(key, value);
        const wrap = document.querySelector(`[data-note-wrap="${key}"]`);
        if (wrap) wrap.classList.add('hidden');
        return;
    }
});

/* ── Voucher ─────────────────────────────────────────────── */
function applyVoucher() {
    const sel = document.getElementById('voucher-select');
    if (!sel) return;
    const opt = sel.selectedOptions[0];
    const voucherRow  = document.getElementById('voucher-row');
    const voucherAmt  = document.getElementById('voucher-discount-amount');
    const discTxt     = document.getElementById('voucher-discount-text');
    const hiddenInput = document.getElementById('selected-voucher-id');
    const applied     = document.getElementById('voucher-applied');

    if (!opt?.value) {
        discountAmount = 0;
        if (voucherRow)  voucherRow.style.display = 'none';
        if (applied)     applied.classList.add('hidden');
        if (hiddenInput) hiddenInput.value = '';
        recalcTotal(); return;
    }

    const type  = opt.dataset.type;
    const value = parseFloat(opt.dataset.value);
    const min   = parseFloat(opt.dataset.min);
    const max   = parseFloat(opt.dataset.max);

    if (rawTotal < min) {
        alert(`Đơn tối thiểu ${formatVND(min)} mới áp dụng được voucher này.`);
        sel.value = ''; discountAmount = 0; recalcTotal(); return;
    }

    discountAmount = type === 'phan_tram'
        ? (max > 0 ? Math.min(rawTotal * value / 100, max) : rawTotal * value / 100)
        : Math.min(value, rawTotal);

    if (hiddenInput) hiddenInput.value = opt.value;
    if (voucherRow)  voucherRow.style.display = '';
    if (voucherAmt)  voucherAmt.textContent  = '−' + formatVND(discountAmount);
    if (applied)     applied.classList.remove('hidden');
    if (discTxt)     discTxt.textContent = '−' + formatVND(discountAmount);
    recalcTotal();
}
document.getElementById('voucher-select')?.addEventListener('change', applyVoucher);

/* ── Toggle Đặt bàn / Gọi món ───────────────────────────── */
function formatLocalDateTime(date) {
    const pad = (n) => String(n).padStart(2, '0');
    const y = date.getFullYear();
    const m = pad(date.getMonth() + 1);
    const d = pad(date.getDate());
    const h = pad(date.getHours());
    const min = pad(date.getMinutes());
    return `${y}-${m}-${d}T${h}:${min}`;
}

function applyArrivalDefaults() {
    const minDate = new Date(Date.now() + 30 * 60 * 1000);
    const minValue = formatLocalDateTime(minDate);
    document.querySelectorAll('input[name="thoi_gian_den"]').forEach((input) => {
        input.min = minValue;
        if (!input.value) {
            input.value = minValue;
        }
    });
}

function toggleOrderType() {
    const rdDatBan = document.getElementById('rd-dat-ban');
    const isDatBan = rdDatBan ? rdDatBan.checked : false;
    ['section-dat-ban','auth-section-dat-ban'].forEach(id =>
        document.getElementById(id)?.classList.toggle('hidden', !isDatBan));
    ['section-goi-mon','auth-section-goi-mon'].forEach(id =>
        document.getElementById(id)?.classList.toggle('hidden', isDatBan));

    document.querySelectorAll('input[name="thoi_gian_den"]').forEach((input) => {
        input.required = !!isDatBan;
    });
    document.querySelectorAll('select[name="ban_an_id_goi_mon"]').forEach((input) => {
        input.required = !isDatBan;
    });
    document.querySelectorAll('select[name="phuong_thuc_thanh_toan_goi_mon"]').forEach((input) => {
        input.required = !isDatBan;
    });

    const loaiVal = isDatBan ? 'dat_ban' : 'goi_mon';
    const gHidden = document.getElementById('guest-loai-don-hidden');
    const aHidden = document.getElementById('auth-loai-don-hidden');
    if (gHidden) gHidden.value = loaiVal;
    if (aHidden) aHidden.value = loaiVal;

    const txt  = rdDatBan ? (isDatBan ? 'Xác nhận đặt bàn trước' : 'Gọi món tại bàn') : 'Gửi yêu cầu gọi món';
    const gBtn = document.getElementById('guest-submit-btn');
    const aBtn = document.getElementById('auth-submit-btn');
    if (gBtn) gBtn.textContent = txt;
    if (aBtn) aBtn.textContent = txt;
}
document.getElementById('rd-dat-ban')?.addEventListener('change', toggleOrderType);
document.getElementById('rd-goi-mon')?.addEventListener('change', toggleOrderType);
applyArrivalDefaults();
toggleOrderType();

/* ── Order modal ────────────────────────────────────────── */
const orderModal = document.getElementById('cart-order-modal');
const openOrderBtn = document.getElementById('open-order-modal');
const closeOrderBtn = document.getElementById('close-order-modal');

function openOrderModal() {
    if (!orderModal) return;
    orderModal.classList.add('open');
    orderModal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('cart-modal-open');
}

function closeOrderModal() {
    if (!orderModal) return;
    orderModal.classList.remove('open');
    orderModal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('cart-modal-open');
}

openOrderBtn?.addEventListener('click', openOrderModal);
closeOrderBtn?.addEventListener('click', closeOrderModal);
orderModal?.addEventListener('click', (e) => {
    if (e.target === orderModal || e.target.classList.contains('cart-order-backdrop')) {
        closeOrderModal();
    }
});
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && orderModal?.classList.contains('open')) {
        closeOrderModal();
    }
});
</script>
@endpush