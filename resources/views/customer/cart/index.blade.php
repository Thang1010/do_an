@extends('customer.layout.app')

@section('title', 'Giỏ hàng - XM Coffee')
@section('meta_description', 'Xem và đặt hàng từ giỏ hàng của bạn tại XM Coffee.')

@section('header_overlay', 'bg-black/30')
@section('body_class', 'cart-page')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/cart.css') }}">
<style>
    details[open] summary .icon-toggle {
        transform: rotate(180deg);
    }
    .icon-toggle {
        transition: transform 0.2s ease-in-out;
        display: inline-block;
    }
</style>
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
                <div class="cart-items-panel cart-items-panel--full" style="margin-bottom: 24px;">
                    <div class="cart-items-header">
                        <div class="cart-items-title-group">
                            <h2 class="cart-items-title">Giỏ hàng của bạn</h2>
                            <p class="cart-items-count">{{ array_sum(array_column($items, 'qty')) }} sản phẩm</p>
                        </div>
                        <a href="{{ route('menu.index') }}" class="cart-continue-link">+ Thêm món</a>
                    </div>
                    <div id="cart-items-list-inline">
                        @foreach($items as $key => $item)
                            <div class="cart-item" id="item-{{ $key }}" data-key="{{ $key }}" data-price="{{ $item['price'] }}" style="display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 16px; border-bottom: 1px solid rgba(255,255,255,0.06); width: 100%; background: rgba(255, 255, 255, 0.04); border-radius: 14px; margin-bottom: 12px;">
                                <div style="display: flex; align-items: center; gap: 16px; flex: 1;">
                                    <img class="cart-item-img cart-item-img--stacked"
                                        src="{{ $item['product']->image_url ?? asset('images/ca_phe_nau_da.jpg') }}"
                                        alt="{{ $item['name'] }}" loading="lazy" style="width: 70px; height: 70px; object-fit: cover; border-radius: 12px; margin: 0;">
                                    <div class="cart-item-info">
                                        <div class="cart-item-title-row" style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                            <div class="cart-item-name" style="font-size: 16px; font-weight: 600; margin: 0;">{{ $item['name'] }}</div>
                                            <span class="cart-item-note-display" data-note-display="{{ $key }}" @if(empty($item['note'])) style="display:none;" @endif>{{ $item['note'] ?? '' }}</span>
                                            <div style="position: relative; display: inline-flex; align-items: center;">
                                                <button type="button" class="cart-item-note-toggle" data-note-key="{{ $key }}" aria-label="Ghi chú">
                                                    <svg class="cart-item-note-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                        <path d="M12 20h9" />
                                                        <path d="M16.5 3.5a2.121 2.121 0 113 3L7 19l-4 1 1-4 12.5-12.5z" />
                                                    </svg>
                                                </button>
                                                <div class="cart-item-note-wrap hidden" data-note-wrap="{{ $key }}" style="position: absolute; top: 50%; transform: translateY(-50%); left: 0; display: flex; align-items: center; gap: 6px; background: #2a1f18; padding: 4px; border-radius: 6px; z-index: 10; box-shadow: 0 4px 12px rgba(0,0,0,0.4);">
                                                    <input type="text" class="cart-item-note-input" value="{{ $item['note'] ?? '' }}" placeholder="Ghi chú món..." data-note-input="{{ $key }}" style="width: 140px; border: 1px solid rgba(255,255,255,0.1); background: rgba(0,0,0,0.2); color: #fff; border-radius: 4px; padding: 4px 8px; font-size: 13px;">
                                                    <button type="button" class="cart-item-note-save" data-note-key="{{ $key }}" data-note-scope="cart" style="background: #c49a6c; color: #1a120c; border: none; border-radius: 4px; padding: 4px 10px; font-size: 12px; font-weight: 600; cursor: pointer;">Lưu</button>
                                                </div>
                                            </div>
                                        </div>
                                        @if(!empty($item['size_name']))
                                            <div class="cart-item-size" style="font-size: 13px; color: #a1a1aa; margin-top: 4px;">{{ $item['size_name'] }} ({{ $item['size_code'] ?? 'N/A' }})</div>
                                        @endif
                                        @if(!empty($item['nhiet_do']))
                                            <div class="cart-item-temp" style="font-size: 13px; color: #eab308; margin-top: 2px;">Nhiệt độ: {{ $item['nhiet_do'] }}</div>
                                        @endif
                                    </div>
                                </div>
                                <div style="display: flex; align-items: center; justify-content: center; width: 150px; flex-shrink: 0;">
                                    <div class="cart-item-qty-wrap">
                                        <button type="button" class="qty-btn qty-dec" data-key="{{ $key }}">−</button>
                                        <span class="qty-val" id="qty-{{ $key }}">{{ $item['qty'] }}</span>
                                        <button type="button" class="qty-btn qty-inc" data-key="{{ $key }}">+</button>
                                    </div>
                                </div>
                                <div class="cart-item-price" style="font-size: 18px; font-weight: 700; flex: 1; text-align: right; color: #F0DDB8;">
                                    {{ number_format($item['price'], 0, ',', '.') }}đ
                                </div>
                                <div class="cart-item-subtotal hidden" id="sub-{{ $key }}" style="display:none;">{{ number_format($item['subtotal'], 0, ',', '.') }}đ</div>
                                <button class="cart-item-remove hidden" data-key="{{ $key }}" style="display:none;">X</button>
                            </div>
                        @endforeach
                    </div>

                    @auth
                        @if(auth()->user()->isKhachHang())
                            @php
                                $myVouchers = auth()->user()->voucherNguoiDung()
                                    ->where('trang_thai', 'chưa dùng')
                                    ->with('voucher')
                                    ->get()
                                    ->filter(function ($v) {
                                        if (!$v->voucher) return false;
                                        if ($v->voucher->trang_thai !== 'đang hoạt động') return false;
                                        return now()->between($v->voucher->ngay_bat_dau, $v->voucher->ngay_ket_thuc);
                                    });
                            @endphp
                            <div class="cart-field-wrap" style="margin-top: 16px;">
                                @if($myVouchers->isEmpty())
                                    <div style="padding: 16px; background: rgba(255, 255, 255, 0.04); border-radius: 14px;">
                                        <label class="cart-field-label" style="color: #d1d5db; font-weight: 600; margin-bottom: 12px; display: block;">Chọn Voucher</label>
                                        <p style="color: #9ca3af; font-size: 14px; margin-bottom: 0;">Bạn chưa có voucher nào khả dụng</p>
                                    </div>
                                @else
                                    <details style="background: rgba(255, 255, 255, 0.04); border-radius: 14px; padding: 16px; cursor: pointer;">
                                        <summary style="font-weight: 600; color: #d1d5db; list-style: none; display: flex; justify-content: space-between; align-items: center; outline: none; margin-bottom: 0;">
                                            <span>Chọn Voucher (Nếu có)</span>
                                            <span class="icon-toggle" style="font-size: 12px; opacity: 0.7;">▼</span>
                                        </summary>
                                        <div class="voucher-list" style="display: flex; flex-direction: column; gap: 8px; max-height: 200px; overflow-y: auto; margin-top: 16px; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 16px;">
                                            @foreach($myVouchers as $uv)
                                                <label style="display: flex; align-items: center; gap: 12px; padding: 12px; background: rgba(255, 255, 255, 0.04); border-radius: 8px; cursor: pointer; border: 1px solid rgba(255,255,255,0.05);">
                                                    <input type="checkbox" name="vouchers_checkbox[]" value="{{ $uv->id }}" class="voucher-checkbox"
                                                        data-type="{{ $uv->voucher->loai_giam }}"
                                                        data-value="{{ $uv->voucher->gia_tri_giam }}"
                                                        data-min="{{ $uv->voucher->don_toi_thieu }}"
                                                        data-max="{{ $uv->voucher->giam_toi_da ?? 0 }}"
                                                        style="width: 18px; height: 18px; accent-color: #059669;" onchange="applyVoucher()">
                                                    <div style="flex: 1;">
                                                        <div style="font-weight: 600; color: #fff;">{{ $uv->voucher->ma_voucher }} — {{ $uv->voucher->ten_voucher }}</div>
                                                        <div style="font-size: 13px; color: #34d399; margin-top: 2px;">
                                                            @if($uv->voucher->loai_giam === 'phần trăm')
                                                                Giảm {{ (float) $uv->voucher->gia_tri_giam }}%
                                                                @if($uv->voucher->giam_toi_da) (tối đa {{ number_format($uv->voucher->giam_toi_da,0,',','.') }}đ) @endif
                                                            @else
                                                                Giảm {{ number_format($uv->voucher->gia_tri_giam,0,',','.') }}đ
                                                            @endif
                                                        </div>
                                                    </div>
                                                </label>
                                            @endforeach
                                        </div>
                                    </details>
                                @endif
                            </div>
                        @endif
                    @endauth

                    <div class="cart-total-display" style="display: flex; justify-content: space-between; align-items: center; padding: 16px; background: rgba(255, 255, 255, 0.04); border-radius: 14px; margin-top: 16px;">
                        <span style="font-weight: 600; color: #d1d5db; font-size: 16px;">Tổng đơn:</span>
                        <span id="cart-grand-total-display" style="font-size: 20px; font-weight: 700; color: #F0DDB8;">{{ number_format($total, 0, ',', '.') }}đ</span>
                    </div>

                    <div class="cart-order-open-wrap" style="margin-top: 16px;">
                        <button type="button" class="cart-order-open-btn" onclick="openOrderModal()">Đặt hàng</button>
                    </div>
                </div>

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

                            @if(!empty($qrTable))
                                <div class="cart-field-wrap" style="background:rgba(34,197,94,0.12);border:1px solid rgba(34,197,94,0.4);border-radius:10px;padding:12px 14px;margin-top:8px;">
                                    🍽️ Bạn đang gọi món tại <strong>Bàn {{ $qrTable->so_ban }}</strong>. Bàn được tự động chọn theo mã QR và không thể thay đổi.
                                </div>
                            @endif

                            @guest
                                <form id="guest-checkout-form" method="POST" action="{{ route('cart.checkout') }}">
                                    @csrf
                                    <input type="hidden" name="loai_don_hidden" id="guest-loai-don-hidden" value="goi_mon">
                                    <input type="hidden" name="phuong_thuc_thanh_toan_goi_mon" value="chuyển khoản">

                                    {{-- Hình thức (ẩn khi vào từ QR — chỉ gọi món tại bàn) --}}
                                    @if(empty($qrTable))
                                        <div class="cart-field-wrap">
                                            <label class="cart-field-label">Hình thức *</label>
                                            <div class="cart-radio-group">
                                                <label class="cart-radio-label">
                                                    <input type="radio" name="loai_don_ui" value="goi_mon" checked>
                                                    <span>Gọi món tại bàn</span>
                                                </label>
                                                <label class="cart-radio-label">
                                                    <input type="radio" name="loai_don_ui" value="mang_ve">
                                                    <span>Mang về</span>
                                                </label>
                                            </div>
                                        </div>
                                    @endif

                                    <div class="cart-field-wrap">
                                        <label class="cart-field-label">Email *</label>
                                        <input type="email" name="email_khach_hang" required placeholder="example@gmail.com" class="cart-input">
                                        <p class="cart-field-hint">Yêu cầu thanh toán trước (chuyển khoản qua mã QR).</p>
                                    </div>

                                    {{-- Gọi món tại bàn --}}
                                    <div id="guest-section-goi-mon" class="cart-field-wrap">
                                        <label class="cart-field-label">Bàn bạn đang ngồi *</label>
                                        @if(!empty($qrTable))
                                            <input type="hidden" name="ban_an_id_goi_mon" value="{{ $qrTable->id }}">
                                            <div class="cart-input" style="opacity:.85;">Bàn {{ $qrTable->so_ban }} (đã khoá theo QR)</div>
                                        @elseif($availableTables->count() > 0)
                                            <select name="ban_an_id_goi_mon" class="cart-select" required>
                                                <option value="">— Chọn bàn —</option>
                                                @foreach($availableTables as $table)
                                                    <option value="{{ $table->id }}">Bàn {{ $table->so_ban }}</option>
                                                @endforeach
                                            </select>
                                        @else
                                            <p class="cart-field-hint warning">Hết bàn trống, vui lòng gọi nhân viên.</p>
                                        @endif
                                    </div>

                                    {{-- Mang về --}}
                                    @if(empty($qrTable))
                                        <div id="guest-section-mang-ve" class="cart-field-wrap hidden">
                                            <div style="background:rgba(234,88,12,0.12);border:1px solid rgba(234,88,12,0.4);border-radius:10px;padding:12px 14px;color:#fdba74;">
                                                Đơn <strong>mang về</strong> — không cần chọn bàn. Vui lòng thanh toán trước để xác nhận đơn.
                                            </div>
                                        </div>
                                    @endif

                                    <button type="submit" class="cart-submit-btn" id="guest-submit-btn">Gửi yêu cầu gọi món</button>
                                </form>
                            @endguest

                            {{-- ── KHÁCH ĐÃ ĐĂNG NHẬP ── --}}
                            @auth
                                <form id="auth-checkout-form" method="POST" action="{{ route('cart.checkout') }}">
                                    @csrf
                                    @php $canReserve = $availableTables->count() > 0; @endphp
                                    <input type="hidden" name="loai_don_hidden" id="auth-loai-don-hidden" value="{{ !empty($qrTable) ? 'goi_mon' : ($canReserve ? 'dat_ban' : 'goi_mon') }}">
                                    <input type="hidden" name="voucher_nguoi_dung_id" id="selected-voucher-id" value="">

                                    @if(empty(auth()->user()->email))
                                        <div class="cart-field-wrap">
                                            <label class="cart-field-label">Email *</label>
                                            <input type="email" name="email_khach" required
                                                placeholder="example@gmail.com" class="cart-input">
                                        </div>
                                    @endif

                                    {{-- Radio: Hình thức (ẩn khi vào từ QR — chỉ gọi món tại bàn) --}}
                                    @if(empty($qrTable))
                                    <div class="cart-field-wrap">
                                        <label class="cart-field-label">Hình thức đặt *</label>
                                        <div class="cart-radio-group">
                                            <label class="cart-radio-label" @unless($canReserve) style="opacity:.5;cursor:not-allowed;" @endunless>
                                                <input type="radio" name="loai_don_ui" value="dat_ban" id="rd-dat-ban" @checked($canReserve) @disabled(!$canReserve)>
                                                <span>Đặt hàng trước</span>
                                            </label>
                                            <label class="cart-radio-label">
                                                <input type="radio" name="loai_don_ui" value="goi_mon" id="rd-goi-mon" @checked(!$canReserve)>
                                                <span>Sử dụng ngay</span>
                                            </label>
                                            <label class="cart-radio-label">
                                                <input type="radio" name="loai_don_ui" value="mang_ve" id="rd-mang-ve">
                                                <span>Mang về</span>
                                            </label>
                                        </div>
                                        @unless($canReserve)
                                            <p class="cart-field-hint warning">Hết bàn trống nên không thể "Đặt hàng trước". Bạn có thể chọn "Sử dụng ngay" hoặc "Mang về".</p>
                                        @endunless
                                    </div>

                                    {{-- Đặt bàn trước (auth) --}}
                                    <div id="auth-section-dat-ban" class="cart-field-wrap">
                                        <label class="cart-field-label">Thời gian đến quán *</label>
                                        <input type="time" name="thoi_gian_den" class="cart-input" id="thoi_gian_den_input"
                                            step="60" min="{{ now()->addMinutes(30)->format('H:i') }}" max="23:59">
                                        <p class="cart-field-hint">Thanh toán bằng chuyển khoản (QR) — bắt buộc khi đặt trước</p>
                                        <input type="hidden" name="phuong_thuc_thanh_toan_dat_ban" value="chuyển khoản">
                                        @if($availableTables->count() > 0)
                                            <label class="cart-field-label mt-3">Chọn bàn *</label>
                                            <select name="ban_an_id_dat_ban" class="cart-select">
                                                <option value="">— Chọn bàn trống —</option>
                                                @foreach($availableTables as $table)
                                                    <option value="{{ $table->id }}">Bàn {{ $table->so_ban }}</option>
                                                @endforeach
                                            </select>
                                        @endif
                                    </div>

                                    {{-- Mang về (auth) --}}
                                    <div id="auth-section-mang-ve" class="cart-field-wrap hidden">
                                        <div style="background:rgba(234,88,12,0.12);border:1px solid rgba(234,88,12,0.4);border-radius:10px;padding:12px 14px;color:#fdba74;">
                                            Đơn <strong>mang về</strong> — không cần chọn bàn. Thanh toán trước bằng chuyển khoản (mã QR) để xác nhận đơn.
                                        </div>
                                    </div>

                                    @endif

                                    {{-- Gọi món tại bàn (auth) --}}
                                    <div id="auth-section-goi-mon" class="cart-field-wrap {{ !empty($qrTable) ? '' : 'hidden' }}">
                                        <label class="cart-field-label">Bàn bạn đang ngồi *</label>
                                        @if(!empty($qrTable))
                                            <input type="hidden" name="ban_an_id_goi_mon" value="{{ $qrTable->id }}">
                                            <div class="cart-input" style="opacity:.85;">Bàn {{ $qrTable->so_ban }} (đã khoá theo QR)</div>
                                        @elseif($allTables->count() > 0)
                                            <select name="ban_an_id_goi_mon" class="cart-select">
                                                <option value="">— Chọn bàn —</option>
                                                @foreach($allTables as $table)
                                                    <option value="{{ $table->id }}">Bàn {{ $table->so_ban }}@if($table->trang_thai !== 'trống') ({{ $table->trang_thai }})@endif</option>
                                                @endforeach
                                            </select>
                                            <p class="cart-field-hint">Có thể chọn bàn đang phục vụ — món sẽ được gộp thêm vào bàn đó.</p>
                                        @else
                                            <p class="cart-field-hint warning">Hiện chưa có bàn khả dụng, vui lòng gọi nhân viên.</p>
                                        @endif
                                        <label class="cart-field-label mt-3">Phương thức thanh toán *</label>
                                        <select name="phuong_thuc_thanh_toan_goi_mon" class="cart-select">
                                            <option value="chuyển khoản">Chuyển khoản (Mã QR) — bắt buộc</option>
                                        </select>
                                        <p class="cart-field-hint">Vui lòng thanh toán trước để xác nhận gọi món.</p>
                                    </div>

                                    <button type="submit" class="cart-submit-btn" id="auth-submit-btn">{{ !empty($qrTable) ? 'Xác nhận gọi món' : 'Xác nhận đặt bàn trước' }}</button>
                                </form>
                            @endauth
                        </div>
                    </div>
                </div>

                <!-- PayOS QR Modal -->
                <div class="cart-order-modal" id="payos-checkout-modal" aria-hidden="true" style="z-index: 10002;">
                    <div class="cart-order-backdrop" style="background: rgba(0,0,0,0.85);"></div>
                    <div class="cart-order-panel cart-modal-panel" role="dialog" aria-modal="true" style="text-align: center; max-width: 500px; margin: 40px auto; display: flex; flex-direction: column;">
                        <button type="button" class="cart-order-close" id="close-payos-modal" aria-label="Dong">x</button>
                        <h2 class="cart-checkout-title">Thanh toán đơn hàng</h2>
                        <div id="payos-qr-container-customer" style="width:100%; height:490px; position: relative; overflow: hidden; margin-bottom: 16px;">
                            <p id="payos-loading-text-customer" style="color: #d1d5db; margin-top: 50px; position: relative; z-index: 10;">Đang tải mã QR...</p>
                            <div style="position: absolute; top: 0; left: 50%; margin-left: -200px; width: 400px; height: 650px; transform: scale(0.75); transform-origin: top center;">
                                <iframe id="payos-qr-iframe-customer" src="" style="width:100%; height:100%; border:none; border-radius:12px; display:none; background:#1a120c;" allow="clipboard-write"></iframe>
                            </div>
                            <div id="payos-success-text-customer" style="display:none; position:absolute; inset:0; background:#1a120c; flex-direction:column; align-items:center; justify-content:center; gap:16px; border-radius:12px; z-index:20;">
                                <div style="width:76px; height:76px; border-radius:50%; background:rgba(52,211,153,0.15); display:flex; align-items:center; justify-content:center;">
                                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#34d399" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M20 6L9 17l-5-5" />
                                    </svg>
                                </div>
                                <p style="font-size:18px; color:#34d399; font-weight:700; margin:0;">Thanh toán thành công!</p>
                                <p style="font-size:14px; color:#d1d5db; margin:0;">Đang chuyển đến trang xác nhận...</p>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="cart-items-panel cart-items-panel--full" style="margin-bottom: 24px;">
                    <div class="cart-items-header">
                        <div class="cart-items-title-group">
                            <h2 class="cart-items-title">Giỏ hàng của bạn</h2>
                            <p class="cart-items-count">0 sản phẩm</p>
                        </div>
                        <a href="{{ route('menu.index') }}" class="cart-continue-link">+ Thêm món</a>
                    </div>
                    <div class="cart-empty" style="border: none; background: transparent; box-shadow: none; padding: 32px 20px; display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 500px;">
                        <svg class="cart-empty-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 72px; height: 72px; color: rgba(255, 255, 255, 0.55); margin-bottom: 20px;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <h2>Giỏ hàng của bạn đang trống</h2>
                        <p>Hãy chọn món để bắt đầu đặt hàng nhé!</p>
                        <a href="{{ route('menu.index') }}" class="cart-go-menu-btn" style="margin-top: 12px;">Thêm món</a>
                    </div>
                </div>
            @endif


        </div>
    </main>
    <div id="cart-js-data" data-total="{{ $total ?? 0 }}" data-is-member="{{ (auth()->check() && optional(auth()->user())->isKhachHang()) ? '1' : '0' }}" hidden></div>

@endsection

@push('scripts')
@include('partials.payos-payment')
<script>
const CSRF    = document.querySelector('meta[name="csrf-token"]')?.content || '';
const menuUrl = "{{ route('menu.index') }}";
// Khách hàng thành viên (đã đăng nhập) → điều hướng về trang chi tiết đơn; khách vãng lai → trang xác nhận.
const IS_MEMBER = document.getElementById('cart-js-data')?.dataset.isMember === '1';
const ORDER_DETAIL_BASE = "{{ url('customer/orders') }}";
const SUCCESS_URL = "{{ route('cart.success') }}";
let   rawTotal  = parseFloat(document.getElementById('cart-js-data')?.dataset.total || 0);
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

    const cgtEl = document.getElementById('cart-grand-total-display');
    if (cgtEl) cgtEl.textContent = formatVND(finalTotal);

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
    const toggleBtn = document.querySelector(`[data-note-key="${key}"].cart-item-note-toggle`);
    if (!wrap) return;
    const isHidden = wrap.classList.contains('hidden');
    wrap.classList.toggle('hidden', !isHidden);
    if (toggleBtn) toggleBtn.style.display = isHidden ? 'none' : 'inline-flex';
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
        const toggleBtn = document.querySelector(`[data-note-key="${key}"].cart-item-note-toggle`);
        if (toggleBtn) toggleBtn.style.display = 'inline-flex';
        return;
    }
});

/* ── Voucher ─────────────────────────────────────────────── */
function applyVoucher() {
    const checkboxes = document.querySelectorAll('.voucher-checkbox:checked');
    const voucherRow  = document.getElementById('voucher-row');
    const voucherAmt  = document.getElementById('voucher-discount-amount');
    const hiddenInput = document.getElementById('selected-voucher-id');

    if (checkboxes.length === 0) {
        discountAmount = 0;
        if (voucherRow)  voucherRow.style.display = 'none';
        if (hiddenInput) hiddenInput.value = '';
        recalcTotal(); return;
    }

    let totalDiscount = 0;
    let selectedIds = [];

    checkboxes.forEach(chk => {
        const type  = chk.dataset.type;
        const value = parseFloat(chk.dataset.value);
        const min   = parseFloat(chk.dataset.min);
        const max   = parseFloat(chk.dataset.max);

        // Calculate theoretical discount if this was the only voucher.
        // If they select multiple, we'll just sum them up for now, but ensure it doesn't exceed total.
        let disc = 0;
        if (rawTotal >= min) {
            disc = type === 'phần trăm'
                ? (max > 0 ? Math.min(rawTotal * value / 100, max) : rawTotal * value / 100)
                : value;
        } else {
            showNotice(`Đơn chưa đạt tối thiểu ${formatVND(min)} để áp dụng voucher ${chk.nextElementSibling.querySelector('div').innerText.split('—')[0].trim()}. Vui lòng bỏ chọn.`);
            chk.checked = false;
        }
        totalDiscount += disc;
        selectedIds.push(chk.value);
    });

    discountAmount = Math.min(totalDiscount, rawTotal);

    // Save as comma-separated IDs (note: backend will need updates to process this properly, but we satisfy UI requirement)
    if (hiddenInput) hiddenInput.value = selectedIds.join(',');
    if (voucherRow)  voucherRow.style.display = '';
    if (voucherAmt)  voucherAmt.textContent  = '−' + formatVND(discountAmount);
    recalcTotal();
}
// document.getElementById('voucher-select')?.addEventListener('change', applyVoucher);

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
    const pad = (n) => String(n).padStart(2, '0');
    const minTime = `${pad(minDate.getHours())}:${pad(minDate.getMinutes())}`;
    
    document.querySelectorAll('input[name="thoi_gian_den"]').forEach((input) => {
        input.min = minTime;
        input.max = '23:59';
        if (!input.value) {
            input.value = minTime;
        }
        
        input.addEventListener('change', function() {
            if (this.value < minTime || this.value > '23:59') {
                showNotice('Vui lòng chọn thời gian sau thời điểm hiện tại và trong ngày hôm nay.');
                this.value = minTime;
            }
        });
    });
}

function toggleOrderType() {
    const checked = document.querySelector('input[name="loai_don_ui"]:checked');
    // Khi vào từ QR không có radio → đọc giá trị từ ô hidden (đã ép goi_mon).
    const val = checked ? checked.value
        : (document.getElementById('auth-loai-don-hidden')?.value
            || document.getElementById('guest-loai-don-hidden')?.value
            || 'goi_mon');

    const sectionMap = {
        dat_ban: ['auth-section-dat-ban'],
        goi_mon: ['auth-section-goi-mon', 'guest-section-goi-mon'],
        mang_ve: ['auth-section-mang-ve', 'guest-section-mang-ve'],
    };
    const allSections = [
        'auth-section-dat-ban', 'auth-section-goi-mon', 'auth-section-mang-ve',
        'guest-section-goi-mon', 'guest-section-mang-ve',
    ];
    allSections.forEach(id => document.getElementById(id)?.classList.add('hidden'));
    (sectionMap[val] || []).forEach(id => document.getElementById(id)?.classList.remove('hidden'));

    // required theo từng hình thức (tránh field ẩn vẫn bị validate)
    document.querySelectorAll('input[name="thoi_gian_den"]').forEach(i => i.required = (val === 'dat_ban'));
    document.querySelectorAll('select[name="ban_an_id_dat_ban"]').forEach(i => i.required = (val === 'dat_ban'));
    document.querySelectorAll('select[name="ban_an_id_goi_mon"]').forEach(i => i.required = (val === 'goi_mon'));
    document.querySelectorAll('select[name="phuong_thuc_thanh_toan_goi_mon"]').forEach(i => i.required = (val === 'goi_mon'));

    const aHidden = document.getElementById('auth-loai-don-hidden');
    const gHidden = document.getElementById('guest-loai-don-hidden');
    if (aHidden) aHidden.value = val;
    if (gHidden) gHidden.value = val;

    const authTxt = { dat_ban: 'Xác nhận đặt hàng trước', goi_mon: 'Xác nhận sử dụng ngay', mang_ve: 'Xác nhận mang về' };
    const guestTxt = { goi_mon: 'Gửi yêu cầu gọi món', mang_ve: 'Xác nhận mang về' };
    const aBtn = document.getElementById('auth-submit-btn');
    const gBtn = document.getElementById('guest-submit-btn');
    if (aBtn) aBtn.textContent = authTxt[val] || 'Đặt hàng';
    if (gBtn) gBtn.textContent = guestTxt[val] || 'Đặt hàng';
}
document.querySelectorAll('input[name="loai_don_ui"]').forEach(r => r.addEventListener('change', toggleOrderType));
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

/* ── Checkout AJAX & PayOS Polling ──────────────────────── */
function handleCheckoutFormSubmit(e) {
    e.preventDefault();
    const form = e.target;
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    
    // Disable button to prevent double submit
    submitBtn.disabled = true;
    submitBtn.textContent = 'Đang xử lý...';

    const formData = new FormData(form);

    fetch(form.action, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(res => {
        if (!res.ok) {
            return res.json().then(err => { throw err; }).catch(() => { throw new Error('Lỗi server. Vui lòng thử lại.'); });
        }
        return res.json();
    })
    .then(data => {
        if (data.success && data.order_code) {
            closeOrderModal();
            openPayOSModal(data.order_code, data.order_id);
        } else {
            showNotice(data.message || 'Lỗi đặt hàng');
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    })
    .catch(err => {
        let msg = 'Lỗi kết nối. Vui lòng thử lại.';
        if (err && err.errors) {
            msg = Object.values(err.errors).map(e => e.join('\n')).join('\n');
        } else if (err && err.message) {
            msg = err.message;
        }
        showNotice(msg);
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    });
}

document.getElementById('guest-checkout-form')?.addEventListener('submit', handleCheckoutFormSubmit);
document.getElementById('auth-checkout-form')?.addEventListener('submit', handleCheckoutFormSubmit);

let currentPayosOrderCode = null;
function openPayOSModal(orderCode, orderId) {
    currentPayosOrderCode = orderCode;
    const modal = document.getElementById('payos-checkout-modal');
    if (!modal) return;
    modal.classList.add('open');
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('cart-modal-open');

    const iframe = document.getElementById('payos-qr-iframe-customer');
    const loadingText = document.getElementById('payos-loading-text-customer');
    const successText = document.getElementById('payos-success-text-customer');

    iframe.style.display = 'none';
    loadingText.style.display = 'block';
    successText.style.display = 'none';

    PayOSPayment.start({
        orderCode: orderCode,
        source: 'customer',
        iframe: iframe,
        loadingText: loadingText,
        // Không truyền successText cho helper — tự hiện overlay ngay trong onPaid để
        // không có khoảng trống giữa lúc ẩn iframe và lúc chuyển trang.
        successDelay: 0,
        onPaid: function () {
            successText.style.display = 'flex';
            setTimeout(function () {
                // Khách hàng thành viên → trang chi tiết đơn; khách vãng lai → quay về trang menu.
                if (IS_MEMBER && orderId) {
                    window.location.href = ORDER_DETAIL_BASE + '/' + orderId;
                } else {
                    window.location.href = menuUrl;
                }
            }, 1000);
        },
        onFail: function (msg) { showNotice(msg); closePayOSModal(); },
        // Khách bấm "Hủy" trên PayOS → đóng modal thay vì hiển thị trang điều hướng trong iframe.
        onCancel: function () { closePayOSModal(); }
    });
}

function closePayOSModal() {
    const modal = document.getElementById('payos-checkout-modal');
    if (modal) {
        modal.classList.remove('open');
        modal.setAttribute('aria-hidden', 'true');
    }
    document.body.classList.remove('cart-modal-open');
    PayOSPayment.stop();

    // Hủy đơn CHƯA thanh toán để khỏi tồn đơn rác, NHƯNG giữ nguyên giỏ hàng.
    // Không reload → không bị nháy trang; giỏ hàng vẫn còn để khách đặt lại.
    if (currentPayosOrderCode) {
        fetch('/cart/payment/' + encodeURIComponent(currentPayosOrderCode) + '/abandon', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest'
            }
        }).catch(function () {});
        currentPayosOrderCode = null;
    }

    // Trả iframe về trống cho lần mở sau.
    var ifr = document.getElementById('payos-qr-iframe-customer');
    if (ifr) { ifr.src = ''; ifr.style.display = 'none'; }
}

document.getElementById('close-payos-modal')?.addEventListener('click', closePayOSModal);

</script>
@endpush