@if(isset($selectedTable) || isset($assignOrder))
    <div class="card">
        <div class="card-header" style="display:flex; align-items:flex-start; justify-content:space-between; gap:10px;">
            <div>
                <span class="card-title">Chi tiết Bàn {{ isset($selectedTable) ? $selectedTable->so_ban : '(Chưa gán)' }}</span>
                @if($selectedOrder)
                    <div style="margin-top:6px;">
                        <span class="badge badge-brew">Đơn #{{ $selectedOrder->ma_don_hang ?? $selectedOrder->id }}</span>
                    </div>
                @endif
            </div>
            @if(isset($selectedTable) && $selectedTable->trang_thai === 'đã đặt')
                <form method="POST" action="{{ route('staff.tables.enter', $selectedTable->id) }}">
                    @csrf @method('PATCH')
                    <button type="submit" class="btn btn-primary btn-sm">Vào bàn</button>
                </form>
            @elseif(isset($selectedTable) && $selectedTable->trang_thai === 'đang phục vụ')
                <form id="release-table-form" method="POST" action="{{ route('staff.tables.release', $selectedTable->id) }}">
                    @csrf @method('PATCH')
                    <button type="button" class="btn btn-secondary btn-sm" onclick="openReleaseTableModal()">Trả bàn</button>
                </form>
            @endif
        </div>
        <div class="card-body">
            @if($selectedItems && $selectedItems->count() > 0)
                @foreach($selectedItems as $item)
                    <div class="order-detail-item">
                        <div class="order-detail-item__info">
                            <div class="order-detail-item__name">{{ $item->ten_san_pham }}</div>
                            @php
                                $sizeCode = $item->kichCo?->ma_kich_co;
                                $sizeName = $item->kichCo?->ten_kich_co ?? $item->ten_kich_co;
                                $sizeLabel = $sizeCode && $sizeName ? $sizeCode . ' - ' . $sizeName : ($sizeName ?? 'M');
                            @endphp
                            <div class="order-detail-item__size">{{ $sizeLabel }}</div>
                            @if(isset($selectedTable))
                                <div id="note-display-{{ $item->id }}" style="display:flex; align-items:center; gap:6px; margin-top:4px;">
                                    <span class="note-snippet {{ $item->ghi_chu_mon ? 'note-snippet--filled' : 'note-snippet--empty' }}">
                                        {{ $item->ghi_chu_mon ?: 'Chưa có ghi chú' }}
                                    </span>
                                    <button type="button" onclick="toggleNoteEdit('{{ $item->id }}')" title="Sửa ghi chú"
                                            style="background:none; border:none; cursor:pointer; color:#8a6d3b; padding:0; display:inline-flex; align-items:center;">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4Z"></path></svg>
                                    </button>
                                </div>
                                <form id="note-form-{{ $item->id }}" method="POST"
                                      action="{{ route('staff.tables.update-item', [$selectedTable->id, $item->id]) }}"
                                      style="display:none; gap:6px; margin-top:4px; align-items:center;">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="action" value="note">
                                    <input type="text" name="ghi_chu_mon" value="{{ $item->ghi_chu_mon }}" maxlength="255"
                                           placeholder="Ghi chú món..."
                                           style="flex:1; min-width:0; font-size:12px; padding:4px 8px; border:1px solid #d6cdbb; border-radius:6px; background:#fffdf8; color:#30261c;">
                                    <button type="submit" class="qty-btn" title="Lưu" style="font-size:12px; padding:4px 10px; width:auto;">Lưu</button>
                                    <button type="button" onclick="toggleNoteEdit('{{ $item->id }}')" class="qty-btn" title="Hủy" style="font-size:12px; padding:4px 8px; width:auto;">✕</button>
                                </form>
                            @elseif($item->ghi_chu_mon)
                                <div class="order-detail-item__note" style="font-size: 13px; color: #eab308; margin-top: 2px;">{{ $item->ghi_chu_mon }}</div>
                            @endif
                        </div>
                        <div class="order-detail-item__qty">
                            <form method="POST" action="{{ isset($selectedTable) ? route('staff.tables.update-item', [$selectedTable->id, $item->id]) : '#' }}" style="display:inline;">
                                @csrf @method('PATCH')
                                <input type="hidden" name="action" value="decrease">
                                <button type="submit" class="qty-btn">−</button>
                            </form>
                            <span class="qty-value">x{{ $item->so_luong }}</span>
                            <form method="POST" action="{{ isset($selectedTable) ? route('staff.tables.update-item', [$selectedTable->id, $item->id]) : '#' }}" style="display:inline;">
                                @csrf @method('PATCH')
                                <input type="hidden" name="action" value="increase">
                                <button type="submit" class="qty-btn">+</button>
                            </form>
                        </div>
                        <div class="order-detail-item__price">
                            {{ number_format(($item->don_gia ?? 0) * ($item->so_luong ?? 1), 0, ',', '.') }}đ
                        </div>
                    </div>
                @endforeach
            @else
                <div class="detail-empty" style="padding: 30px 0;">
                    <div class="detail-empty__text">Chưa có món nào</div>
                </div>
            @endif
        </div>
    </div>

    <form method="POST" action="{{ isset($selectedTable) ? route('staff.tables.order.update', $selectedTable->id) : '#' }}" id="order-update-form" style="margin-top:16px;">
        @csrf @method('PATCH')
        <input type="hidden" name="order_id" value="{{ $selectedOrder?->id }}">
        <input type="hidden" name="category" value="{{ request('category') }}">
        <input type="hidden" name="auto_voucher" id="auto-voucher-flag" value="0">

        @php
            $hasItemsForDiscount = $selectedOrder || (isset($selectedItems) && count($selectedItems) > 0);
            $hasCustomerVoucher = $selectedOrder && $selectedOrder->voucher_nguoi_dung_id;
            $appliedDiscount = $selectedOrder ? (float) $selectedOrder->so_tien_giam : 0;
            $discountSubtotal = 0;
            if (isset($selectedItems)) {
                foreach ($selectedItems as $dItem) {
                    $discountSubtotal += ($dItem->don_gia ?? 0) * ($dItem->so_luong ?? 1);
                }
            }
            // Chiết khấu thủ công (không phải voucher của khách) để giữ qua các lần tạm tính
            $manualDiscount = (!$hasCustomerVoucher && $appliedDiscount > 0) ? $appliedDiscount : 0;
        @endphp

        <input type="hidden" name="chiet_khau_loai" id="chiet-khau-loai" value="{{ $manualDiscount > 0 ? 'tiền' : '' }}">
        <input type="hidden" name="chiet_khau_gia_tri" id="chiet-khau-gia-tri" value="{{ $manualDiscount > 0 ? number_format($manualDiscount, 0, '.', '') : '0' }}">

        @if(isset($selectedOrder) && $appliedDiscount > 0)
            <div class="order-total" style="color: #10b981; font-size: 14px; border-top: none; padding-top: 0; padding-bottom: 8px;">
                <span class="order-total__label" style="color: #10b981;">{{ $hasCustomerVoucher ? 'Đã giảm giá (Khách đặt):' : 'Chiết khấu:' }}</span>
                <span class="order-total__value">-{{ number_format($appliedDiscount, 0, ',', '.') }}đ</span>
            </div>
        @endif
        <div class="order-total">
            <span class="order-total__label">Tổng cộng:</span>
            <span class="order-total__value">{{ number_format($displayTotal ?? 0, 0, ',', '.') }}đ</span>
        </div>

        <div class="order-actions">
            @if(!isset($selectedTable))
                <div style="color: #d97706; text-align: center; font-size: 0.9rem; padding: 10px; background: #fef3c7; border-radius: 8px; width: 100%;">
                    Vui lòng chọn bàn ở danh sách bên trái để gán đơn hàng.
                </div>
            @elseif($selectedOrder && $selectedOrder->trang_thai_thanh_toan === 'đã thanh toán')
                <div style="color: #16a34a; text-align: center; font-size: 0.9rem; padding: 10px; background: #dcfce7; border-radius: 8px; width: 100%; font-weight: 600;">
                    Đơn hàng đã thanh toán. Vui lòng trả bàn.
                </div>
            @else
                <button type="submit" name="action" value="draft" class="btn btn-secondary w-full"
                    style="justify-content:center;" {{ count($selectedItems ?? []) === 0 ? 'disabled' : '' }}>Tạm tính</button>
                @if(!$hasCustomerVoucher)
                    <button type="button" id="discount-trigger" class="btn w-full"
                        style="justify-content:center; background:#8a6d3b; color:#fff; border:none;"
                        onclick="openDiscountModal()"
                        data-subtotal="{{ $discountSubtotal }}"
                        data-current="{{ $manualDiscount > 0 ? number_format($manualDiscount, 0, '.', '') : '0' }}"
                        {{ count($selectedItems ?? []) === 0 ? 'disabled' : '' }}>Chiết khấu</button>
                @endif
                <button type="submit" name="action" value="payment" class="btn btn-primary w-full"
                    style="justify-content:center;" {{ count($selectedItems ?? []) === 0 ? 'disabled' : '' }}>Thanh toán</button>
            @endif
        </div>
    </form>

    {{-- Nút xóa thông tin bàn — nằm riêng 1 hàng bên dưới --}}
    @if(isset($selectedTable) && $selectedTable->trang_thai === 'đang phục vụ' && (!$selectedOrder || $selectedOrder->trang_thai_thanh_toan === 'chưa thanh toán'))
        <div style="margin-top: 10px;">
            <button type="button" class="btn w-full" style="justify-content:center;background:#d92d20;color:#fff;border:none;"
                    onclick="openClearTablePanelModal()">
                Xóa thông tin bàn
            </button>
        </div>
    @endif

    {{-- Hidden form for clear table (outside main form to avoid nesting) --}}
    @if(isset($selectedTable) && $selectedTable->trang_thai === 'đang phục vụ')
        <form id="clear-table-panel-form" method="POST" action="{{ route('staff.tables.clear', $selectedTable->id) }}" style="display:none;">
            @csrf @method('DELETE')
        </form>
    @endif

    <div class="modal" id="discount-modal" aria-hidden="true">
        <div class="modal__backdrop" onclick="closeDiscountModal()"></div>
        <div class="modal__content modal__content--sm">
            <div class="modal__header">
                <div class="modal__title">Chiết khấu hóa đơn</div>
                <button type="button" class="modal__close" onclick="closeDiscountModal()">&times;</button>
            </div>
            <div class="modal__body">
                <div class="form-group">
                    <label class="form-label" style="color:#F0DDB8;">Kiểu chiết khấu</label>
                    <div style="display:flex; gap:8px;">
                        <button type="button" class="payment-method is-active" id="discount-type-percent" onclick="setDiscountType('phần trăm')">Theo %</button>
                        <button type="button" class="payment-method" id="discount-type-amount" onclick="setDiscountType('tiền')">Theo tiền</button>
                    </div>
                </div>
                <div class="form-group" style="margin-top:14px;">
                    <label class="form-label" id="discount-value-label" style="color:#F0DDB8;">Phần trăm giảm (%)</label>
                    <input type="number" min="0" step="0.01" id="discount-value-input" class="form-control"
                           placeholder="Nhập giá trị" oninput="updateDiscountPreview()">
                    <p id="discount-hint" style="font-size:12px; color:#9b8e77; margin-top:6px;">—</p>
                </div>
            </div>
            <div class="modal__footer" style="justify-content:space-between;">
                <button type="button" class="btn btn-secondary" onclick="clearDiscount()">Bỏ chiết khấu</button>
                <button type="button" class="btn btn-primary" onclick="applyDiscount()">Áp dụng</button>
            </div>
        </div>
    </div>

    @if($selectedOrder && isset($selectedTable))
        <div class="modal" id="payment-modal"
             data-auto-open="{{ request()->boolean('payment') ? '1' : '0' }}">
            <div class="modal__backdrop"></div>
            <div class="modal__content">
                <div class="modal__header">
                    <div class="modal__title">Thanh toán - Bàn {{ $selectedTable->so_ban }}</div>
                    <button type="button" class="modal__close" data-modal-close>&times;</button>
                </div>
                <div class="modal__body payment-modal__body">
                    <div class="payment-modal__summary">
                        <div class="payment-modal__subtitle">Tóm tắt đơn hàng</div>
                        <div class="payment-modal__items">
                            @forelse($selectedItems as $item)
                                <div class="payment-modal__item">
                                    <div class="payment-modal__item-name">{{ $item->ten_san_pham }}</div>
                                    <div class="payment-modal__item-qty">x{{ $item->so_luong }}</div>
                                    <div class="payment-modal__item-price">
                                        {{ number_format(($item->don_gia ?? 0) * ($item->so_luong ?? 1), 0, ',', '.') }}đ
                                    </div>
                                </div>
                            @empty
                                <div class="detail-empty__text">Chưa có món nào</div>
                            @endforelse
                        </div>
                        <div class="payment-modal__total">
                            <span>Tổng cộng:</span>
                            <span class="payment-modal__total-value">{{ number_format($selectedOrder->tong_tien ?? 0, 0, ',', '.') }}đ</span>
                        </div>
                    </div>
                    <div class="payment-modal__method">
                        <div class="payment-modal__subtitle" style="margin-bottom:8px;">Email nhận hóa đơn (Tuỳ chọn)</div>
                        <input form="payment-modal-form" type="email" name="email_khach_hang" value="{{ $selectedOrder->email_khach_hang ?? $selectedOrder->nguoiDung?->email ?? '' }}" id="payment-modal-email" placeholder="email@example.com" class="form-control" style="margin-bottom:20px;">
                        
                        <div class="payment-modal__subtitle">Phương thức thanh toán</div>
                        <div class="payment-modal__methods">
                            <button type="button" class="payment-method" data-payment-method="tiền mặt">Tiền mặt</button>
                            <button type="button" class="payment-method is-active" data-payment-method="chuyển khoản">Chuyển khoản</button>
                        </div>
                        <div class="payment-modal__qr" id="payment-modal-qr" style="display:none; margin-top: 16px; text-align: center;">
                            <div id="payos-qr-container-staff" style="width:100%; height:490px; display:none; position: relative; overflow: hidden;">
                                <div style="position: absolute; top: 0; left: 50%; margin-left: -200px; width: 400px; height: 650px; transform: scale(0.75); transform-origin: top center;">
                                    <iframe id="payos-qr-iframe-staff" src="" style="width:100%; height:100%; border:none; border-radius:12px; display:none;" allow="clipboard-write"></iframe>
                                </div>
                                <p style="font-size:15px;color:#16a34a;font-weight:700;display:none;position:absolute;bottom:0;width:100%;text-align:center;background:#fff;padding:8px 0;" id="payos-success-text-staff">Đã thanh toán thành công!</p>
                            </div>
                            <button type="button" class="btn btn-primary" id="btn-generate-payos-staff" style="display: inline-flex; justify-content: center; width: 100%;" data-order-code="{{ $selectedOrder->ma_don_hang }}" onclick="generatePayOSQrStaff(this.dataset.orderCode)">
                                Tạo QR thanh toán PayOS
                            </button>
                        </div>
                    </div>
                </div>
                <div class="modal__footer payment-modal__footer" id="payment-modal-footer">
                    <form method="POST" action="{{ route('staff.tables.payment.update', $selectedTable->id) }}" class="payment-modal__actions" id="payment-modal-form">
                        @csrf @method('PATCH')
                        <input type="hidden" name="order_id" value="{{ $selectedOrder->id }}">
                        <input type="hidden" name="phuong_thuc_thanh_toan" id="payment-method-input" value="chuyển khoản">
                        <input type="hidden" name="trang_thai_thanh_toan" value="đã thanh toán">
                        <button type="button" class="btn btn-secondary" data-modal-close>Hủy</button>
                        <button type="submit" class="btn btn-primary" id="payment-modal-submit">Hoàn tất thanh toán</button>
                    </form>
                </div>
            </div>
        </div>
    @endif
@else
    <div class="card">
        <div class="card-body">
            <div class="detail-empty">
                <div class="detail-empty__text">Chọn một bàn để xem chi tiết</div>
            </div>
        </div>
    </div>
@endif

{{-- Clear table modal for POS panel --}}
@if(isset($selectedTable) && $selectedTable->trang_thai === 'đang phục vụ')
<div id="clear-table-panel-modal" style="position:fixed;inset:0;display:none;align-items:center;justify-content:center;z-index:10001;padding:20px;" role="dialog" aria-modal="true">
    <div onclick="closeClearTablePanelModal()" style="position:absolute;inset:0;background:rgba(18,12,8,0.72);backdrop-filter:blur(2px);"></div>
    <div style="position:relative;width:min(460px,92vw);background:rgba(30,17,6,0.92);border-radius:18px;border:1px solid rgba(240,221,184,0.16);backdrop-filter:blur(14px);padding:28px 26px 22px;box-shadow:0 24px 60px rgba(0,0,0,0.45);font-family:'Outfit',sans-serif;">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px;">
            <div style="width:44px;height:44px;border-radius:50%;background:rgba(217, 45, 32, 0.15);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#d92d20" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            </div>
            <div style="font-size:18px;font-weight:700;color:#F0DDB8;">Xóa thông tin bàn {{ $selectedTable->so_ban }}</div>
            <button type="button" onclick="closeClearTablePanelModal()" style="margin-left:auto;background:none;border:none;cursor:pointer;color:rgba(255,255,255,0.5);font-size:20px;line-height:1;">&times;</button>
        </div>
        <p style="font-size:14px;color:rgba(255,255,255,0.78);margin-bottom:8px;line-height:1.6;">
            Hành động này sẽ <strong>xóa toàn bộ đơn hàng chưa thanh toán</strong> và tất cả món ăn trong bàn hiện tại.
        </p>
        <p style="font-size:14px;color:#ff6b6b;font-weight:600;margin-bottom:22px;">
            Bàn sẽ trở về trạng thái <em>trống</em>. Dữ liệu đơn hàng sẽ bị xóa vĩnh viễn và không thể khôi phục.
        </p>
        <div style="display:flex;gap:10px;justify-content:flex-end;">
            <button type="button" onclick="closeClearTablePanelModal()" style="padding:10px 22px;border-radius:8px;border:1px solid rgba(240,221,184,0.3);background:rgba(255,255,255,0.05);color:#F0DDB8;font-size:14px;font-weight:600;cursor:pointer;font-family:'Outfit',sans-serif;">Hủy</button>
            <button type="button" onclick="document.getElementById('clear-table-panel-form').submit()" style="padding:10px 22px;border-radius:8px;border:none;background:#d92d20;color:#fff;font-size:14px;font-weight:600;cursor:pointer;font-family:'Outfit',sans-serif;">Xác nhận xóa bàn</button>
        </div>
    </div>
</div>
@include('partials.payos-payment')
<script>
function openClearTablePanelModal() {
    var m = document.getElementById('clear-table-panel-modal');
    if (m) { m.style.display = 'flex'; document.body.style.overflow = 'hidden'; }
}
function closeClearTablePanelModal() {
    var m = document.getElementById('clear-table-panel-modal');
    if (m) { m.style.display = 'none'; document.body.style.overflow = ''; }
}
function openReleaseTableModal() {
    var m = document.getElementById('release-table-modal');
    if (m) { m.style.display = 'flex'; document.body.style.overflow = 'hidden'; }
}
function closeReleaseTableModal() {
    var m = document.getElementById('release-table-modal');
    if (m) { m.style.display = 'none'; document.body.style.overflow = ''; }
}

function generatePayOSQrStaff(orderCode) {
    const emailInput = document.getElementById('payment-modal-email');
    PayOSPayment.start({
        orderCode: orderCode,
        source: 'staff',
        email: emailInput ? emailInput.value : '',
        button: document.getElementById('btn-generate-payos-staff'),
        iframe: document.getElementById('payos-qr-iframe-staff'),
        container: document.getElementById('payos-qr-container-staff'),
        successText: document.getElementById('payos-success-text-staff'),
        onPaid: function () { window.location.reload(); }
    });
}
</script>
@endif

{{-- Release table modal for POS panel --}}
@if(isset($selectedTable) && $selectedTable->trang_thai === 'đang phục vụ')
<div id="release-table-modal" style="position:fixed;inset:0;display:none;align-items:center;justify-content:center;z-index:10001;padding:20px;" role="dialog" aria-modal="true">
    <div onclick="closeReleaseTableModal()" style="position:absolute;inset:0;background:rgba(18,12,8,0.72);backdrop-filter:blur(2px);"></div>
    <div style="position:relative;width:min(460px,92vw);background:rgba(30,17,6,0.92);border-radius:18px;border:1px solid rgba(240,221,184,0.16);backdrop-filter:blur(14px);padding:28px 26px 22px;box-shadow:0 24px 60px rgba(0,0,0,0.45);font-family:'Outfit',sans-serif;">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px;">
            @if($selectedTableHasUnpaid ?? false)
                <div style="width:44px;height:44px;border-radius:50%;background:rgba(255,107,107,0.15);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#ff6b6b" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                </div>
                <div style="font-size:18px;font-weight:700;color:#F0DDB8;">Không thể trả bàn {{ $selectedTable->so_ban }}</div>
            @else
                <div style="width:44px;height:44px;border-radius:50%;background:rgba(22,163,74,0.18);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#34d399" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                </div>
                <div style="font-size:18px;font-weight:700;color:#F0DDB8;">Xác nhận trả bàn {{ $selectedTable->so_ban }}</div>
            @endif
            <button type="button" onclick="closeReleaseTableModal()" style="margin-left:auto;background:none;border:none;cursor:pointer;color:rgba(255,255,255,0.55);font-size:20px;line-height:1;">&times;</button>
        </div>

        @if($selectedTableHasUnpaid ?? false)
            <p style="font-size:14px;color:#ff8a8a;font-weight:600;margin-bottom:22px;line-height:1.6;">
                Bàn này vẫn còn đơn chưa thanh toán. Vui lòng thanh toán tất cả các đơn để trả bàn.
            </p>
            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <button type="button" onclick="closeReleaseTableModal()" style="padding:10px 22px;border-radius:8px;border:1px solid rgba(240,221,184,0.3);background:rgba(255,255,255,0.05);color:#F0DDB8;font-size:14px;font-weight:600;cursor:pointer;font-family:'Outfit',sans-serif;">Đóng</button>
            </div>
        @else
            <p style="font-size:14px;color:rgba(255,255,255,0.78);margin-bottom:22px;line-height:1.6;">
                Bạn có chắc chắn muốn trả bàn không? Bàn sẽ trở về trạng thái trống.
            </p>
            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <button type="button" onclick="closeReleaseTableModal()" style="padding:10px 22px;border-radius:8px;border:1px solid rgba(240,221,184,0.3);background:rgba(255,255,255,0.05);color:#F0DDB8;font-size:14px;font-weight:600;cursor:pointer;font-family:'Outfit',sans-serif;">Hủy</button>
                <button type="button" onclick="document.getElementById('release-table-form').submit()" style="padding:10px 22px;border-radius:8px;border:none;background:#16a34a;color:#fff;font-size:14px;font-weight:600;cursor:pointer;font-family:'Outfit',sans-serif;">Xác nhận trả bàn</button>
            </div>
        @endif
    </div>
</div>
@endif
