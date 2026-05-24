@if(isset($selectedTable) || isset($assignOrder))
    <div class="card">
        <div class="card-header">
            <span class="card-title">Chi tiết Bàn {{ isset($selectedTable) ? $selectedTable->so_ban : '(Chưa gán)' }}</span>
            @if($selectedOrder)
                <span class="badge badge-brew">Đơn #{{ $selectedOrder->ma_don_hang ?? $selectedOrder->id }}</span>
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

    <form method="POST" action="{{ isset($selectedTable) ? route('staff.tables.order.update', $selectedTable->id) : '#' }}" id="order-update-form">
        @csrf @method('PATCH')
        <input type="hidden" name="order_id" value="{{ $selectedOrder?->id }}">
        <input type="hidden" name="category" value="{{ request('category') }}">
        <input type="hidden" name="auto_voucher" id="auto-voucher-flag" value="0">

        <div class="voucher-row">
            <div class="voucher-row__header">
                <label class="form-label">Voucher</label>
                <button type="button" class="voucher-detail-link" id="voucher-detail-trigger" {{ !$selectedOrder ? 'disabled' : '' }}>
                    Chi tiết
                </button>
            </div>
            <select name="voucher_id" class="form-control" id="voucher-select" {{ !$selectedOrder ? 'disabled' : '' }}>
                <option value="">Không dùng voucher</option>
                @foreach($availableVouchers as $voucher)
                    @php
                        $percentValue = rtrim(rtrim(number_format($voucher->gia_tri_giam, 2, ',', '.'), '0'), ',');
                        $discountLabel = $voucher->loai_giam === 'phần trăm'
                            ? $percentValue . '%'
                            : number_format($voucher->gia_tri_giam, 0, ',', '.') . 'đ';
                        $minLabel = (float) $voucher->don_toi_thieu > 0
                            ? number_format($voucher->don_toi_thieu, 0, ',', '.') . 'đ'
                            : 'Không';
                    @endphp
                    <option value="{{ $voucher->id }}"
                            data-code="{{ $voucher->ma_voucher }}"
                            data-name="{{ $voucher->ten_voucher }}"
                            data-discount="{{ $discountLabel }}"
                            data-discount-type="{{ $voucher->loai_giam }}"
                            data-min="{{ $minLabel }}"
                            {{ (string) $selectedVoucherId === (string) $voucher->id ? 'selected' : '' }}>
                        {{ $voucher->ma_voucher }} — {{ $voucher->ten_voucher }} ({{ $discountLabel }})
                    </option>
                @endforeach
            </select>
        </div>

        <div class="order-total">
            <span class="order-total__label">Tổng cộng:</span>
            <span class="order-total__value">{{ number_format($selectedOrder->tong_tien ?? 0, 0, ',', '.') }}đ</span>
        </div>

        <div class="order-actions">
            @if(!isset($selectedTable))
                <div style="color: #d97706; text-align: center; font-size: 0.9rem; padding: 10px; background: #fef3c7; border-radius: 8px; width: 100%;">
                    Vui lòng chọn bàn ở danh sách bên trái để gán đơn hàng.
                </div>
            @elseif($selectedOrder && in_array($selectedOrder->trang_thai_don, ['chờ xác nhận', 'cho_xac_nhan']))
                <button type="submit" name="action" value="reject" class="btn btn-secondary w-full"
                        style="justify-content:center; background-color: #dc2626; color: white; border-color: #dc2626;" onclick="return confirm('Bạn có chắc muốn từ chối đơn này?');">Từ chối đơn</button>
                <button type="submit" name="action" value="confirm" class="btn btn-primary w-full"
                        style="justify-content:center;">Xác nhận đơn</button>
            @else
                <button type="submit" name="action" value="draft" class="btn btn-secondary w-full"
                        style="justify-content:center;" {{ !$selectedOrder ? 'disabled' : '' }}>Tạm tính</button>
                <button type="submit" name="action" value="payment" class="btn btn-primary w-full"
                        style="justify-content:center;" {{ !$selectedOrder ? 'disabled' : '' }}>Thanh toán</button>
            @endif
        </div>
    </form>

    <div class="modal" id="voucher-modal" aria-hidden="true">
        <div class="modal__backdrop" data-modal-close></div>
        <div class="modal__content modal__content--sm">
            <div class="modal__header">
                <div class="modal__title">Chi tiết voucher</div>
                <button type="button" class="modal__close" data-modal-close>&times;</button>
            </div>
            <div class="modal__body">
                <div class="voucher-modal__title" id="voucher-modal-title">—</div>
                <div class="voucher-modal__row"><span>Mã voucher</span><strong id="voucher-modal-code">—</strong></div>
                <div class="voucher-modal__row"><span>Đơn tối thiểu</span><strong id="voucher-modal-min">—</strong></div>
                <div class="voucher-modal__row"><span>Giảm giá</span><strong id="voucher-modal-discount">—</strong></div>
            </div>
            <div class="modal__footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Đóng</button>
            </div>
        </div>
    </div>

    @if($selectedOrder && isset($selectedTable))
        <div class="modal" id="payment-modal"
             data-auto-open="{{ request()->boolean('payment') ? '1' : '0' }}"
             data-qr-url="{{ route('staff.tables.payment-qr', $selectedTable->id) }}">
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
                        <div class="payment-modal__subtitle">Phương thức thanh toán</div>
                        <div class="payment-modal__methods">
                            <button type="button" class="payment-method" data-payment-method="tiền mặt">Tiền mặt</button>
                            <button type="button" class="payment-method is-active" data-payment-method="chuyển khoản">Chuyển khoản</button>
                        </div>
                        <div class="payment-modal__qr" id="payment-modal-qr">
                            <img id="payment-qr-image" src="" alt="QR Thanh toán">
                            <div class="payment-modal__qr-note" id="payment-qr-note">Đang tạo mã QR...</div>
                            <div class="payment-modal__bank">
                                <div class="payment-modal__bank-row"><span>Ngân hàng</span><strong id="payment-bank-name">—</strong></div>
                                <div class="payment-modal__bank-row"><span>STK</span><strong id="payment-bank-account">—</strong></div>
                                <div class="payment-modal__bank-row"><span>Chủ TK</span><strong id="payment-bank-owner">—</strong></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal__footer payment-modal__footer">
                    <form method="POST" action="{{ route('staff.tables.payment.update', $selectedTable->id) }}" class="payment-modal__actions">
                        @csrf @method('PATCH')
                        <input type="hidden" name="order_id" value="{{ $selectedOrder->id }}">
                        <input type="hidden" name="phuong_thuc_thanh_toan" id="payment-method-input" value="chuyển khoản">
                        <input type="hidden" name="trang_thai_thanh_toan" value="đã thanh toán">
                        <button type="button" class="btn btn-secondary" data-modal-close>Hủy</button>
                        <button type="submit" class="btn btn-primary">Hoàn tất thanh toán</button>
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
