@extends('layouts.manager')

@section('title', 'Chi tiết bàn ăn')
@section('breadcrumb')
Kinh doanh / <a href="{{ route('manager.tables.index') }}">Quản lý bàn ăn</a> / <strong>Chi tiết bàn</strong>
@endsection

@section('content')

@php
    $latestCustomerName = $latestOrder?->nguoiDung?->ho_ten ?? $latestOrder?->ten_khach_hang ?? '—';
    $latestCustomerPhone = $latestOrder?->nguoiDung?->so_dien_thoai ?? $latestOrder?->so_dien_thoai_khach ?? '—';
    $latestStaffName = $latestOrder?->nhanVien?->ho_ten ?? 'Chưa có';
    $latestPaymentMethod = $latestOrder?->phuong_thuc_thanh_toan ?? '—';
    $latestPaymentStatus = $latestOrder?->trang_thai_thanh_toan ?? '—';
    $latestPaymentStatusClass = match ($latestPaymentStatus) {
        'đã thanh toán' => 'badge-success',
        'chưa thanh toán' => 'badge-warning',
        'thất bại' => 'badge-danger',
        default => 'badge-default',
    };
@endphp

<div class="page-header">
    <div>
        <h1 class="page-title">Chi tiết bàn {{ $table->so_ban }}</h1>
        <p class="page-subtitle">Danh sách món đã gọi tại bàn này{{ $latestOrder ? ' • Đơn gần nhất #' . $latestOrder->id : '' }}</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('manager.tables.index') }}" class="btn btn-secondary">Quay lại</a>
    </div>
</div>

<div class="card mb-20">
    <div class="card-header">
        <span class="card-title">Thông tin sản phẩm</span>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Tên sản phẩm</th>
                    <th>Size</th>
                    <th>Số lượng</th>
                    <th>Ghi chú món</th>
                </tr>
            </thead>
            <tbody>
                @forelse($dishItems ?? [] as $item)
                @php
                    $sizeSymbol = $item->kichCo->ma_kich_co
                        ?? (!empty($item->ten_kich_co) ? mb_strtoupper(mb_substr(trim($item->ten_kich_co), 0, 1)) : 'M');
                @endphp
                <tr>
                    <td>
                        <div class="font-600">{{ $item->ten_san_pham ?? '—' }}</div>
                    </td>
                    <td><span class="badge badge-default">{{ $sizeSymbol }}</span></td>
                    <td>{{ number_format($item->so_luong ?? 0, 0, ',', '.') }}</td>
                    <td class="text-muted">{{ $item->ghi_chu_mon ?: '—' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="empty-state">
                        Bàn này chưa có món ăn nào được gọi.
                    </td>
                </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <td class="text-right text-12 text-muted font-600">Voucher đã sử dụng</td>
                    <td class="font-600">{{ $voucherSummary }}</td>
                    <td class="text-right text-12 text-muted font-600">Số tiền đã giảm</td>
                    <td class="font-600">{{ number_format($totalDiscount ?? 0, 0, ',', '.') }}đ</td>
                </tr>
                <tr>
                    <td colspan="3" class="text-right text-12 text-muted font-600">Tổng tiền cần trả</td>
                    <td class="price-text text-22 font-700">{{ number_format($totalPayable ?? 0, 0, ',', '.') }}đ</td>
                </tr>
            </tfoot>
        </table>
    </div>

    @if(isset($dishItems) && method_exists($dishItems, 'hasPages') && $dishItems->hasPages())
    <div class="card-footer">
        <div class="pagination-footer">
            <span class="pagination-info">
                Hiển thị {{ $dishItems->firstItem() }}-{{ $dishItems->lastItem() }} / {{ $dishItems->total() }} món
            </span>
            {{ $dishItems->links() }}
        </div>
    </div>
    @endif
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title">Thông tin đơn hàng</span>
    </div>
    <div class="card-body">
        <div class="form-grid-2">
            <div>
                <div class="text-12 text-muted">Tên người dùng</div>
                <div class="font-600">{{ $latestCustomerName }}</div>
            </div>
            <div>
                <div class="text-12 text-muted">Tên nhân viên nhận đơn</div>
                <div class="font-600">{{ $latestStaffName }}</div>
            </div>
            <div>
                <div class="text-12 text-muted">Số điện thoại</div>
                <div class="font-600">{{ $latestCustomerPhone }}</div>
            </div>
            <div>
                <div class="text-12 text-muted">Bàn</div>
                <div class="font-600">{{ $table->so_ban ?? '—' }}</div>
            </div>
            <div>
                <div class="text-12 text-muted">Phương thức thanh toán</div>
                <div class="font-600">{{ $latestPaymentMethod }}</div>
            </div>
            <div>
                <div class="text-12 text-muted">Trạng thái thanh toán</div>
                <div class="font-600"><span class="badge {{ $latestPaymentStatusClass }}">{{ $latestPaymentStatus }}</span></div>
            </div>
        </div>

        @if($latestOrder && auth()->check() && in_array(auth()->user()->vai_tro ?? '', ['quản lý', 'nhân viên'], true))
        <form method="POST" action="{{ route('manager.tables.payment.update', $table->id) }}" style="margin-top: 18px; border-top: 1px dashed #e6ded2; padding-top: 14px;">
            @csrf
            @method('PATCH')
            <input type="hidden" name="order_id" value="{{ $latestOrder->id }}">
            <div class="text-12 text-muted" style="margin-bottom: 10px;">
                Cập nhật thủ công thanh toán cho đơn gần nhất #{{ $latestOrder->id }}
            </div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label">Phương thức thanh toán</label>
                    <select name="phuong_thuc_thanh_toan" class="form-control">
                        <option value="">Chưa chọn</option>
                        <option value="tiền mặt" {{ ($latestOrder->phuong_thuc_thanh_toan ?? '') === 'tiền mặt' ? 'selected' : '' }}>Tiền mặt</option>
                        <option value="chuyển khoản" {{ ($latestOrder->phuong_thuc_thanh_toan ?? '') === 'chuyển khoản' ? 'selected' : '' }}>Chuyển khoản</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Trạng thái thanh toán</label>
                    <select name="trang_thai_thanh_toan" class="form-control" required>
                        <option value="chưa thanh toán" {{ ($latestOrder->trang_thai_thanh_toan ?? '') === 'chưa thanh toán' ? 'selected' : '' }}>Chưa thanh toán</option>
                        <option value="đã thanh toán" {{ ($latestOrder->trang_thai_thanh_toan ?? '') === 'đã thanh toán' ? 'selected' : '' }}>Đã thanh toán</option>
                        <option value="thất bại" {{ ($latestOrder->trang_thai_thanh_toan ?? '') === 'thất bại' ? 'selected' : '' }}>Thất bại</option>
                    </select>
                </div>
            </div>
            <div style="display: flex; justify-content: flex-end; margin-top: 8px;">
                <button type="submit" class="btn btn-primary btn-sm">Lưu thanh toán</button>
            </div>
        </form>
        @endif

        <div style="margin-top: 18px; border-top: 1px dashed #e6ded2; padding-top: 14px;">
            <div class="text-12 text-muted" style="margin-bottom: 10px;">Tạo mã QR thanh toán nhanh (hiệu lực 60 giây)</div>
            <button type="button"
                    class="btn btn-primary btn-sm"
                    id="generate-payment-qr-btn"
                    data-url="{{ route('manager.tables.payment-qr', $table->id) }}">
                Tạo mã QR thanh toán
            </button>

            <div id="payment-qr-message" class="text-12 text-muted" style="margin-top: 10px;">Nhấn nút để tạo mã QR theo đơn chưa thanh toán gần nhất.</div>

            <div id="payment-qr-panel" style="display: none; margin-top: 12px;">
                <img id="payment-qr-image" src="" alt="QR thanh toán" style="width: 220px; height: 220px; border-radius: 10px; border: 1px solid #e6ded2; background: #fff;">
                <div class="text-12" style="margin-top: 10px;">
                    <div><strong>Ngân hàng:</strong> <span id="payment-qr-bank">—</span></div>
                    <div><strong>Số tài khoản:</strong> <span id="payment-qr-account">—</span></div>
                    <div><strong>Số tiền:</strong> <span id="payment-qr-amount">0đ</span></div>
                    <div><strong>Nội dung CK:</strong> <span id="payment-qr-content">—</span></div>
                    <div style="margin-top: 6px;"><strong>Hết hiệu lực sau:</strong> <span id="payment-qr-countdown">60</span>s</div>
                </div>

                <form id="confirm-qr-paid-form"
                      method="POST"
                      action="{{ route('manager.tables.payment.update', $table->id) }}"
                      style="margin-top: 10px;"
                      onsubmit="return confirm('Xác nhận đã nhận tiền qua QR cho đơn này?')">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="order_id" id="confirm-qr-order-id" value="{{ $latestOrder?->id }}">
                    <input type="hidden" name="phuong_thuc_thanh_toan" value="chuyển khoản">
                    <input type="hidden" name="trang_thai_thanh_toan" value="đã thanh toán">
                    <button type="submit" class="btn btn-secondary btn-sm">Xác nhận đã nhận tiền QR</button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
(function () {
    const btn = document.getElementById('generate-payment-qr-btn');
    if (!btn) {
        return;
    }

    const panel = document.getElementById('payment-qr-panel');
    const message = document.getElementById('payment-qr-message');
    const qrImage = document.getElementById('payment-qr-image');
    const bankEl = document.getElementById('payment-qr-bank');
    const accountEl = document.getElementById('payment-qr-account');
    const amountEl = document.getElementById('payment-qr-amount');
    const contentEl = document.getElementById('payment-qr-content');
    const countdownEl = document.getElementById('payment-qr-countdown');
    const confirmOrderIdInput = document.getElementById('confirm-qr-order-id');
    let countdownTimer = null;

    function setMessage(text, isError) {
        message.textContent = text;
        message.style.color = isError ? '#b42318' : '#5f544a';
    }

    function stopCountdown() {
        if (countdownTimer) {
            clearInterval(countdownTimer);
            countdownTimer = null;
        }
    }

    function startCountdown(seconds) {
        stopCountdown();
        let remain = Number(seconds) || 60;
        countdownEl.textContent = remain;

        countdownTimer = setInterval(function () {
            remain -= 1;
            countdownEl.textContent = Math.max(remain, 0);
            if (remain <= 0) {
                stopCountdown();
                panel.style.display = 'none';
                qrImage.src = '';
                setMessage('Mã QR đã hết hiệu lực. Vui lòng tạo mã mới.', true);
            }
        }, 1000);
    }

    btn.addEventListener('click', async function () {
        btn.disabled = true;
        setMessage('Đang tạo mã QR thanh toán...', false);

        try {
            const response = await fetch(btn.dataset.url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const payload = await response.json();

            if (!response.ok) {
                throw new Error(payload.message || 'Không thể tạo mã QR thanh toán.');
            }

            qrImage.src = payload.qr_url;
            bankEl.textContent = payload.bank_name || payload.bank_code || '—';
            accountEl.textContent = payload.account_no || '—';
            amountEl.textContent = new Intl.NumberFormat('vi-VN').format(payload.amount || 0) + 'đ';
            contentEl.textContent = payload.transfer_content || '—';
            if (confirmOrderIdInput && payload.order_id) {
                confirmOrderIdInput.value = payload.order_id;
            }
            panel.style.display = 'block';
            startCountdown(payload.expires_in || 60);
            setMessage(payload.message || 'Đã tạo mã QR thanh toán.', false);
        } catch (error) {
            panel.style.display = 'none';
            qrImage.src = '';
            stopCountdown();
            setMessage(error.message || 'Có lỗi khi tạo mã QR.', true);
        } finally {
            btn.disabled = false;
        }
    });
})();
</script>
@endpush
