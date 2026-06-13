@extends('manager.layout.app')

@section('title', 'Chi tiết bàn ăn')
@section('breadcrumb')
    Kinh doanh / <a href="{{ route('manager.tables.index') }}">Quản lý bàn ăn</a> / <strong>Chi tiết bàn</strong>
@endsection

@section('content')

    @php
        $latestCustomerName = $latestOrder?->nguoiDung?->hoSoKhachHang?->ho_ten ?? $latestOrder?->nguoiDung?->email ?? '—';
        $latestCustomerPhone = $latestOrder?->nguoiDung?->hoSoNhanVien?->so_dien_thoai ?? '—';
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
            <h1 class="page-title" style="display: flex; align-items: center; gap: 10px;">
                Chi tiết bàn {{ $table->so_ban }}
                @if($latestOrder)
                    @php
                        $prominentStyle = '';
                        if ($latestPaymentStatus === 'đã thanh toán') {
                            $prominentStyle = 'background-color: #28a745; color: white; font-size: 14px; padding: 4px 12px; border-radius: 6px; font-weight: 600; text-transform: uppercase; box-shadow: 0 2px 4px rgba(0,0,0,0.1);';
                        } elseif ($latestPaymentStatus === 'chưa thanh toán') {
                            $prominentStyle = 'background-color: #ffc107; color: #212529; font-size: 14px; padding: 4px 12px; border-radius: 6px; font-weight: 600; text-transform: uppercase; box-shadow: 0 2px 4px rgba(0,0,0,0.1);';
                        } else {
                            $prominentStyle = 'background-color: #6c757d; color: white; font-size: 14px; padding: 4px 12px; border-radius: 6px; font-weight: 600; text-transform: uppercase; box-shadow: 0 2px 4px rgba(0,0,0,0.1);';
                        }
                    @endphp
                    <span style="{{ $prominentStyle }}">{{ mb_convert_case($latestPaymentStatus, MB_CASE_TITLE, 'UTF-8') }}</span>
                @endif
            </h1>
            <p class="page-subtitle">Danh sách món đã gọi tại bàn
                này{{ $latestOrder ? ' • Đơn gần nhất #' . $latestOrder->id : '' }}</p>
        </div>
        <div class="page-actions">
            @if(auth()->check() && in_array(auth()->user()->vai_tro ?? '', ['quản lý', 'nhân viên', 'chủ cửa hàng'], true))
                @if($table->trang_thai === 'đã đặt')
                    <form method="POST" action="{{ route('manager.tables.enter', $table->id) }}" style="display: inline-block;">
                        @csrf @method('PATCH')
                        <button type="submit" class="btn btn-primary">Vào bàn</button>
                    </form>
                @elseif($table->trang_thai === 'đang phục vụ')

                    <form id="release-table-form" method="POST" action="{{ route('manager.tables.release', $table->id) }}"
                        style="display: inline-block;">
                        @csrf @method('PATCH')
                        @if($tableHasUnpaid ?? false)
                            <button type="button" class="btn btn-secondary" onclick="openUnpaidAlertModal()">
                                Trả bàn
                            </button>
                        @else
                            <button type="button" class="btn btn-secondary" onclick="openReleaseConfirmModal()">
                                Trả bàn
                            </button>
                        @endif
                    </form>


                    {{-- Nút xóa thông tin bàn --}}
                    @if(!$latestOrder || $tableHasUnpaid)
                        <form id="clear-table-form" method="POST" action="{{ route('manager.tables.clear', $table->id) }}"
                            style="display: inline-block;">
                            @csrf @method('DELETE')
                        </form>
                        <button type="button" class="btn btn-danger btn-sm" onclick="openClearTableModal()"
                            style="background:#d92d20;color:#fff;border:none;">
                            Xóa thông tin bàn
                        </button>
                    @endif
                @endif
            @endif
            <a href="{{ route('manager.tables.index') }}" class="btn btn-secondary">Quay lại</a>
        </div>
    </div>

    <div class="card mb-20">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; gap: 8px;">
            <span class="card-title">Thông tin sản phẩm</span>
            <div style="display: flex; gap: 8px;">
                @if(in_array($table->trang_thai, ['đang phục vụ', 'đã đặt']) && (!$latestOrder || $tableHasUnpaid))
                    <button type="button" class="btn btn-primary btn-sm" onclick="openModal('add-item-modal')">Thêm món</button>
                @endif
                @if($tableHasUnpaid)
                    <button type="button" class="btn btn-success btn-sm" onclick="openModal('payment-modal')">Thanh
                        toán</button>
                @endif
            </div>
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
                        <td class="text-right text-12 text-muted font-600">Mã giảm giá đã sử dụng</td>
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

    @if($latestOrder && $latestPaymentStatus === 'đã thanh toán')
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
                        <div class="font-600"><span
                                class="badge {{ $latestPaymentStatusClass }}">{{ $latestPaymentStatus }}</span></div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Clear table confirmation modal --}}
    @if($table->trang_thai === 'đang phục vụ')
        <div id="clear-table-modal"
            style="position:fixed;inset:0;display:none;align-items:center;justify-content:center;z-index:10001;padding:20px;"
            role="dialog" aria-modal="true">
            <div data-backdrop-clear style="position:absolute;inset:0;background:rgba(18,12,8,0.72);backdrop-filter:blur(2px);">
            </div>
            <div
                style="position:relative;width:min(460px,92vw);background:rgba(30,17,6,0.92);border-radius:18px;border:1px solid rgba(240,221,184,0.16);backdrop-filter:blur(14px);padding:28px 26px 22px;box-shadow:0 24px 60px rgba(0,0,0,0.45);font-family:'Outfit',sans-serif;">
                <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px;">
                    <div
                        style="width:44px;height:44px;border-radius:50%;background:rgba(217, 45, 32, 0.15);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#d92d20" stroke-width="2.2"
                            stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10" />
                            <line x1="12" y1="8" x2="12" y2="12" />
                            <line x1="12" y1="16" x2="12.01" y2="16" />
                        </svg>
                    </div>
                    <div style="font-size:18px;font-weight:700;color:#F0DDB8;">Xóa thông tin bàn {{ $table->so_ban }}</div>
                    <button type="button" data-close-clear
                        style="margin-left:auto;background:none;border:none;cursor:pointer;color:rgba(255,255,255,0.5);font-size:20px;line-height:1;">&times;</button>
                </div>
                <p style="font-size:14px;color:rgba(255,255,255,0.78);margin-bottom:8px;line-height:1.6;">
                    Hành động này sẽ <strong>xóa toàn bộ đơn hàng chưa thanh toán</strong> và tất cả món ăn trong bàn hiện tại.
                </p>
                <p style="font-size:14px;color:#ff6b6b;font-weight:600;margin-bottom:22px;">
                    Bàn sẽ trở về trạng thái <em>trống</em>. Dữ liệu đơn hàng sẽ bị xóa vĩnh viễn và không thể khôi phục.
                </p>
                <div style="display:flex;gap:10px;justify-content:flex-end;">
                    <button type="button" data-cancel-clear
                        style="padding:10px 22px;border-radius:8px;border:1px solid rgba(240,221,184,0.3);background:rgba(255,255,255,0.05);color:#F0DDB8;font-size:14px;font-weight:600;cursor:pointer;font-family:'Outfit',sans-serif;">Hủy</button>
                    <button type="button" data-confirm-clear
                        style="padding:10px 22px;border-radius:8px;border:none;background:#d92d20;color:#fff;font-size:14px;font-weight:600;cursor:pointer;font-family:'Outfit',sans-serif;">Xác
                        nhận xóa bàn</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Add Item Modal --}}
    @if($tableHasUnpaid ?? false)
        <div id="unpaid-alert-modal"
            style="position:fixed;inset:0;display:none;align-items:center;justify-content:center;z-index:10001;padding:20px;"
            role="dialog" aria-modal="true">
            <div onclick="closeUnpaidAlertModal()"
                style="position:absolute;inset:0;background:rgba(18,12,8,0.72);backdrop-filter:blur(2px);"></div>
            <div
                style="position:relative;width:min(460px,92vw);background:rgba(30,17,6,0.92);border-radius:18px;border:1px solid rgba(240,221,184,0.16);backdrop-filter:blur(14px);padding:28px 26px 22px;box-shadow:0 24px 60px rgba(0,0,0,0.45);font-family:'Outfit',sans-serif;text-align:center;">
                <div
                    style="width:56px;height:56px;border-radius:50%;background:rgba(133, 100, 4, 0.2);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2.2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10" />
                        <line x1="12" y1="8" x2="12" y2="12" />
                        <line x1="12" y1="16" x2="12.01" y2="16" />
                    </svg>
                </div>
                <div style="font-size:18px;font-weight:700;color:#F0DDB8;margin-bottom:12px;">Đơn chưa thanh toán</div>
                <p style="font-size:14px;color:rgba(255,255,255,0.78);margin-bottom:22px;line-height:1.6;">
                    Bàn hiện tại có đơn hàng <strong>chưa thanh toán</strong>. Bạn cần thanh toán hóa đơn này hoặc chọn "Xóa
                    thông tin bàn" để có thể trả bàn.
                </p>
                <button type="button" onclick="closeUnpaidAlertModal()"
                    style="padding:10px 22px;border-radius:8px;border:none;background:#c49a6c;color:#1a120c;font-size:14px;font-weight:600;cursor:pointer;font-family:'Outfit',sans-serif;width:100%;">Đã
                    hiểu</button>
            </div>
        </div>
    @endif

    {{-- Release Confirm Modal --}}
    <div id="release-confirm-modal"
        style="position:fixed;inset:0;display:none;align-items:center;justify-content:center;z-index:10001;padding:20px;"
        role="dialog" aria-modal="true">
        <div onclick="closeReleaseConfirmModal()"
            style="position:absolute;inset:0;background:rgba(18,12,8,0.72);backdrop-filter:blur(2px);"></div>
        <div
            style="position:relative;width:min(460px,92vw);background:rgba(30,17,6,0.92);border-radius:18px;border:1px solid rgba(240,221,184,0.16);backdrop-filter:blur(14px);padding:28px 26px 22px;box-shadow:0 24px 60px rgba(0,0,0,0.45);font-family:'Outfit',sans-serif;text-align:center;">
            <div
                style="width:56px;height:56px;border-radius:50%;background:rgba(0, 123, 255, 0.15);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2.2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                </svg>
            </div>
            <div style="font-size:18px;font-weight:700;color:#F0DDB8;margin-bottom:12px;">Xác nhận trả bàn</div>
            <p style="font-size:14px;color:rgba(255,255,255,0.78);margin-bottom:22px;line-height:1.6;">
                Bạn chắc chắn muốn trả bàn chứ? Bàn sẽ được chuyển về trạng thái <strong style="color:#fff">Trống</strong>.
            </p>
            <div style="display:flex;gap:10px;justify-content:center;">
                <button type="button" onclick="closeReleaseConfirmModal()"
                    style="padding:10px 22px;border-radius:8px;border:1px solid rgba(240,221,184,0.3);background:rgba(255,255,255,0.05);color:#F0DDB8;font-size:14px;font-weight:600;cursor:pointer;font-family:'Outfit',sans-serif;">Hủy</button>
                <button type="button" onclick="document.getElementById('release-table-form').submit()"
                    style="padding:10px 22px;border-radius:8px;border:none;background:#3b82f6;color:#fff;font-size:14px;font-weight:600;cursor:pointer;font-family:'Outfit',sans-serif;">Trả
                    bàn</button>
            </div>
        </div>
    </div>

    {{-- QR Payment Confirm Modal --}}
    <div id="qr-payment-confirm-modal"
        style="position:fixed;inset:0;display:none;align-items:center;justify-content:center;z-index:10001;padding:20px;"
        role="dialog" aria-modal="true">
        <div onclick="closeQrPaymentConfirmModal()"
            style="position:absolute;inset:0;background:rgba(18,12,8,0.72);backdrop-filter:blur(2px);"></div>
        <div
            style="position:relative;width:min(460px,92vw);background:rgba(30,17,6,0.92);border-radius:18px;border:1px solid rgba(240,221,184,0.16);backdrop-filter:blur(14px);padding:28px 26px 22px;box-shadow:0 24px 60px rgba(0,0,0,0.45);font-family:'Outfit',sans-serif;text-align:center;">
            <div
                style="width:56px;height:56px;border-radius:50%;background:rgba(40, 167, 69, 0.15);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2.2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                    <polyline points="22 4 12 14.01 9 11.01" />
                </svg>
            </div>
            <div style="font-size:18px;font-weight:700;color:#F0DDB8;margin-bottom:12px;">Xác nhận nhận tiền</div>
            <p style="font-size:14px;color:rgba(255,255,255,0.78);margin-bottom:22px;line-height:1.6;">
                Bạn chắc chắn đã nhận được tiền rồi chứ?
            </p>
            <div style="display:flex;gap:10px;justify-content:center;">
                <button type="button" onclick="closeQrPaymentConfirmModal()"
                    style="padding:10px 22px;border-radius:8px;border:1px solid rgba(240,221,184,0.3);background:rgba(255,255,255,0.05);color:#F0DDB8;font-size:14px;font-weight:600;cursor:pointer;font-family:'Outfit',sans-serif;">Kiểm
                    tra lại</button>
                <button type="button" onclick="document.getElementById('confirm-qr-paid-form').submit()"
                    style="padding:10px 22px;border-radius:8px;border:none;background:#22c55e;color:#fff;font-size:14px;font-weight:600;cursor:pointer;font-family:'Outfit',sans-serif;">Đã
                    nhận đủ</button>
            </div>
        </div>
    </div>

    {{-- Cash Payment Confirm Modal --}}
    <div id="cash-payment-confirm-modal"
        style="position:fixed;inset:0;display:none;align-items:center;justify-content:center;z-index:10002;padding:20px;"
        role="dialog" aria-modal="true">
        <div onclick="closeCashPaymentConfirmModal()"
            style="position:absolute;inset:0;background:rgba(18,12,8,0.72);backdrop-filter:blur(2px);"></div>
        <div
            style="position:relative;width:min(460px,92vw);background:rgba(30,17,6,0.92);border-radius:18px;border:1px solid rgba(240,221,184,0.16);backdrop-filter:blur(14px);padding:28px 26px 22px;box-shadow:0 24px 60px rgba(0,0,0,0.45);font-family:'Outfit',sans-serif;text-align:center;">
            <div
                style="width:56px;height:56px;border-radius:50%;background:rgba(40, 167, 69, 0.15);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2.2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2" y="6" width="20" height="12" rx="2" />
                    <circle cx="12" cy="12" r="2.5" />
                    <path d="M6 10v4M18 10v4" />
                </svg>
            </div>
            <div style="font-size:18px;font-weight:700;color:#F0DDB8;margin-bottom:12px;">Xác nhận thanh toán tiền mặt</div>
            <p style="font-size:14px;color:rgba(255,255,255,0.78);margin-bottom:22px;line-height:1.6;">
                Bạn đã nhận đủ <strong style="color:#fff">{{ number_format($totalPayable ?? 0, 0, ',', '.') }}đ</strong>
                tiền mặt cho đơn này chưa?
            </p>
            <div style="display:flex;gap:10px;justify-content:center;">
                <button type="button" onclick="closeCashPaymentConfirmModal()"
                    style="padding:10px 22px;border-radius:8px;border:1px solid rgba(240,221,184,0.3);background:rgba(255,255,255,0.05);color:#F0DDB8;font-size:14px;font-weight:600;cursor:pointer;font-family:'Outfit',sans-serif;">Kiểm
                    tra lại</button>
                <button type="button" onclick="submitCashPaymentConfirm()"
                    style="padding:10px 22px;border-radius:8px;border:none;background:#22c55e;color:#fff;font-size:14px;font-weight:600;cursor:pointer;font-family:'Outfit',sans-serif;">Đã
                    nhận đủ</button>
            </div>
        </div>
    </div>

    {{-- Add Item Modal --}}
    @if(in_array($table->trang_thai, ['đang phục vụ', 'đã đặt']) && (!$latestOrder || $tableHasUnpaid))
        <div class="modal-backdrop" id="add-item-modal" data-auto-open="{{ ($errors->any() && old('items')) ? '1' : '0' }}">
            <div class="modal-box" style="max-width: 900px; width: calc(100% - 32px);">
                <div class="modal-header">
                    <span class="modal-title">Thêm món vào bàn {{ $table->so_ban }}</span>
                    <button type="button" class="modal-close" onclick="closeModal('add-item-modal')">&#x2715;</button>
                </div>
                <div class="modal-body">
                    <form id="add-item-form" method="POST" action="{{ route('manager.tables.add-item', $table->id) }}">
                        @csrf

                        <div style="display: flex; align-items: center; justify-content: space-between; margin: 0 0 8px;">
                            <label class="form-label" style="margin: 0;">Danh sách món <span>*</span></label>
                            <button type="button" id="add-order-item" class="btn btn-secondary btn-sm">+ Thêm món</button>
                        </div>

                        <div id="order-items-container" style="display: grid; gap: 10px;">
                            <div class="order-item-row" style="border: 1px solid #eee; border-radius: 10px; padding: 10px;">
                                <div
                                    style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                    <strong class="order-item-label">Món 1</strong>
                                    <button type="button" class="btn btn-danger btn-sm btn-remove-order-item"
                                        style="display: none;">Xóa món</button>
                                </div>

                                <div class="grid" style="grid-template-columns: 1.5fr 1.2fr 0.8fr; gap: 10px;">
                                    <div class="form-group" style="margin: 0;">
                                        <label class="form-label">Sản phẩm</label>
                                        <select class="form-control js-product-select" data-field="san_pham_id" required>
                                            <option value="">Chọn sản phẩm</option>
                                            @foreach($availableProducts ?? [] as $product)
                                                <option value="{{ $product->id }}" {{ (string) old('items.0.san_pham_id') === (string) $product->id ? 'selected' : '' }}>
                                                    {{ $product->ten_san_pham }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="form-group" style="margin: 0;">
                                        <label class="form-label">Kích cỡ</label>
                                        <select class="form-control js-size-select" data-field="kich_co_id">
                                            <option value="">Không chọn kích cỡ</option>
                                        </select>
                                    </div>

                                    <div class="form-group" style="margin: 0;">
                                        <label class="form-label">Số lượng</label>
                                        <input type="number" class="form-control" data-field="so_luong" min="1" step="1"
                                            value="{{ old('items.0.so_luong', 1) }}" required>
                                    </div>
                                </div>

                                <div class="form-group" style="margin-top: 8px; margin-bottom: 0;">
                                    <label class="form-label">Ghi chú món</label>
                                    <input type="text" class="form-control" data-field="ghi_chu_mon" maxlength="255"
                                        value="{{ old('items.0.ghi_chu_mon') }}" placeholder="Ít đá, ít ngọt...">
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('add-item-modal')">Hủy</button>
                    <button type="submit" form="add-item-form" class="btn btn-primary">Lưu (Tạm tính)</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Payment Modal --}}
    @if($tableHasUnpaid && $latestOrder)
        <div class="modal-backdrop" id="payment-modal">
            <div class="modal-box" style="max-width: 600px; width: calc(100% - 32px);">
                <div class="modal-header">
                    <span class="modal-title">Thanh toán đơn hàng #{{ $latestOrder->id }} - Bàn {{ $table->so_ban }}</span>
                    <button type="button" class="modal-close" onclick="closeModal('payment-modal')">&#x2715;</button>
                </div>
                <div class="modal-body">
                    <div class="text-12 text-muted" style="margin-bottom: 10px;">
                        Cập nhật thanh toán cho đơn gần nhất #{{ $latestOrder->id }} (bắt buộc chọn phương thức)
                    </div>
                    <form method="POST" action="{{ route('manager.tables.payment.update', $table->id) }}"
                        id="table-payment-method-form">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="order_id" value="{{ $latestOrder->id }}">
                        <input type="hidden" name="trang_thai_thanh_toan"
                            value="{{ $latestOrder->trang_thai_thanh_toan ?? 'chưa thanh toán' }}">
                        <div class="form-grid-1" style="margin-bottom: 16px;">
                            <div class="form-group">
                                <label class="form-label">Email nhận hóa đơn (Tuỳ chọn)</label>
                                <input type="email" name="email_khach_hang" value="{{ $latestOrder->email_khach_hang ?? $latestOrder->nguoiDung?->email ?? '' }}" placeholder="email@example.com" class="form-control">
                            </div>
                        </div>
                        <div class="form-grid-2">
                            <div class="form-group">
                                <label class="form-label">Phương thức thanh toán <span>*</span></label>
                                <select name="phuong_thuc_thanh_toan" id="table-payment-method-select" class="form-control"
                                    required>
                                    <option value="" disabled {{ empty($latestOrder->phuong_thuc_thanh_toan) ? 'selected' : '' }}>Chọn phương thức</option>
                                    <option value="tiền mặt" {{ ($latestOrder->phuong_thuc_thanh_toan ?? '') === 'tiền mặt' ? 'selected' : '' }}>Tiền mặt</option>
                                    <option value="chuyển khoản" {{ ($latestOrder->phuong_thuc_thanh_toan ?? '') === 'chuyển khoản' ? 'selected' : '' }}>Chuyển khoản</option>
                                </select>
                            </div>
                        </div>
                    </form>

                    <div id="table-payment-actions"
                        data-paid="{{ ($latestOrder->trang_thai_thanh_toan ?? '') === 'đã thanh toán' ? '1' : '0' }}">
                        <div id="table-payment-paid" class="text-12 text-muted" style="margin-top: 10px; display: none;">
                            Đơn hàng đã thanh toán thành công.
                        </div>
                        <div id="table-payment-hint" class="text-12 text-muted" style="margin-top: 10px; display: none;">
                            Vui lòng chọn phương thức thanh toán để tiếp tục.
                        </div>
                        <div id="table-cash-action" style="margin-top: 12px; display: none;">
                            <form method="POST" action="{{ route('manager.tables.payment.update', $table->id) }}"
                                id="confirm-cash-paid-form">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="order_id" value="{{ $latestOrder->id }}">
                                <input type="hidden" name="phuong_thuc_thanh_toan" value="tiền mặt">
                                <input type="hidden" name="trang_thai_thanh_toan" value="đã thanh toán">
                                <input type="hidden" name="email_khach_hang" id="table-cash-email-input" value="">
                                <button type="button" class="btn btn-primary w-full" style="justify-content:center;"
                                    onclick="openCashPaymentConfirmModal()">Xác nhận đã
                                    thu {{ number_format($totalPayable ?? 0, 0, ',', '.') }}đ tiền mặt</button>
                            </form>
                        </div>
                        <div id="table-transfer-action" style="margin-top: 12px; display: none; text-align: center;">
                            <div id="payos-qr-container-manager" style="width:100%; height:490px; display:none; position: relative; overflow: hidden; margin-bottom: 16px;">
                                <div style="position: absolute; top: 0; left: 50%; margin-left: -200px; width: 400px; height: 650px; transform: scale(0.75); transform-origin: top center;">
                                    <iframe id="payos-qr-iframe-manager" src="" style="width:100%; height:100%; border:none; border-radius:12px; display:none;" allow="clipboard-write"></iframe>
                                </div>
                                <p style="font-size:15px;color:#16a34a;font-weight:700;display:none;position:absolute;bottom:0;width:100%;text-align:center;background:#fff;padding:8px 0;" id="payos-success-text-manager">Đã thanh toán thành công!</p>
                            </div>
                            <button type="button" class="btn btn-primary" id="btn-generate-payos-manager"
                                style="display: inline-flex; justify-content: center; width: 100%;"
                                onclick="generatePayOSQrManager('{{ $latestOrder->ma_don_hang ?? '' }}')">
                                Tạo QR thanh toán PayOS
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

@endsection

@push('scripts')
@include('partials.payos-payment')
    <script>


        document.addEventListener('DOMContentLoaded', function () {
            var clearModal = document.getElementById('clear-table-modal');
            if (!clearModal) return;
            var clearForm = document.getElementById('clear-table-form');
            var closeBtn = clearModal.querySelector('[data-close-clear]');
            var cancelBtn = clearModal.querySelector('[data-cancel-clear]');
            var confirmBtn = clearModal.querySelector('[data-confirm-clear]');
            var backdrop = clearModal.querySelector('[data-backdrop-clear]');

            if (confirmBtn) confirmBtn.addEventListener('click', function () {
                if (clearForm) clearForm.submit();
            });
            if (cancelBtn) cancelBtn.addEventListener('click', closeClearModal);
            if (closeBtn) closeBtn.addEventListener('click', closeClearModal);
            if (backdrop) backdrop.addEventListener('click', closeClearModal);
        });

        window.openClearTableModal = function () {
            var clearModal = document.getElementById('clear-table-modal');
            if (clearModal) {
                clearModal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
            }
        };

        function closeClearModal() {
            var clearModal = document.getElementById('clear-table-modal');
            if (clearModal) {
                clearModal.style.display = 'none';
                document.body.style.overflow = '';
            }
        }

        window.openUnpaidAlertModal = function () {
            var m = document.getElementById('unpaid-alert-modal');
            if (m) { m.style.display = 'flex'; document.body.style.overflow = 'hidden'; }
        };

        window.closeUnpaidAlertModal = function () {
            var m = document.getElementById('unpaid-alert-modal');
            if (m) { m.style.display = 'none'; document.body.style.overflow = ''; }
        };

        window.openReleaseConfirmModal = function () {
            var m = document.getElementById('release-confirm-modal');
            if (m) { m.style.display = 'flex'; document.body.style.overflow = 'hidden'; }
        };

        window.closeReleaseConfirmModal = function () {
            var m = document.getElementById('release-confirm-modal');
            if (m) { m.style.display = 'none'; document.body.style.overflow = ''; }
        };

        window.openQrPaymentConfirmModal = function () {
            var m = document.getElementById('qr-payment-confirm-modal');
            if (m) { m.style.display = 'flex'; document.body.style.overflow = 'hidden'; }
        };

        window.closeQrPaymentConfirmModal = function () {
            var m = document.getElementById('qr-payment-confirm-modal');
            if (m) { m.style.display = 'none'; document.body.style.overflow = ''; }
        };

        window.openCashPaymentConfirmModal = function () {
            var m = document.getElementById('cash-payment-confirm-modal');
            if (m) { m.style.display = 'flex'; document.body.style.overflow = 'hidden'; }
        };

        window.closeCashPaymentConfirmModal = function () {
            var m = document.getElementById('cash-payment-confirm-modal');
            if (m) { m.style.display = 'none'; document.body.style.overflow = ''; }
        };

        window.submitCashPaymentConfirm = function () {
            var emailInput = document.querySelector('#payment-modal input[name="email_khach_hang"]');
            var cashEmail = document.getElementById('table-cash-email-input');
            if (emailInput && cashEmail) cashEmail.value = emailInput.value;
            document.getElementById('confirm-cash-paid-form').submit();
        };


    </script>
    <script id="order-product-map-data" type="application/json">@json($productSizeMap ?? [])</script>
    <script>
        const orderProductMap = JSON.parse(
            document.getElementById('order-product-map-data')?.textContent || '{}'
        );
        const shouldOpenAddItemModal =
            document.getElementById('add-item-modal')?.dataset.autoOpen === '1';

        function syncOrderItemNames() {
            const rows = document.querySelectorAll('#order-items-container .order-item-row');
            rows.forEach((row, index) => {
                row.querySelector('.order-item-label').textContent = `Món ${index + 1}`;

                row.querySelectorAll('[data-field]').forEach((input) => {
                    const field = input.getAttribute('data-field');
                    input.setAttribute('name', `items[${index}][${field}]`);
                });

                const removeBtn = row.querySelector('.btn-remove-order-item');
                if (removeBtn) {
                    removeBtn.style.display = rows.length === 1 ? 'none' : 'inline-flex';
                }
            });
        }

        function buildSizeOptions(sizeSelect, productId) {
            if (!sizeSelect) return;

            const options = [{ id: '', name: 'Không chọn kích cỡ' }];
            const productInfo = orderProductMap[String(productId)] || orderProductMap[productId];
            if (productInfo && Array.isArray(productInfo.sizes)) {
                productInfo.sizes.forEach((size) => options.push({ id: size.id, name: size.name }));
            }

            const previousValue = sizeSelect.value;
            sizeSelect.innerHTML = options
                .map((opt) => `<option value="${opt.id}">${opt.name}</option>`)
                .join('');

            if (previousValue && options.some((opt) => String(opt.id) === String(previousValue))) {
                sizeSelect.value = previousValue;
            }
        }

        function createOrderItemRow() {
            const container = document.getElementById('order-items-container');
            const firstRow = container.querySelector('.order-item-row');
            if (!firstRow) return;

            const newRow = firstRow.cloneNode(true);

            newRow.querySelectorAll('input').forEach((input) => {
                if (input.type === 'number') {
                    input.value = '1';
                } else {
                    input.value = '';
                }
            });

            newRow.querySelectorAll('select').forEach((select) => {
                select.selectedIndex = 0;
            });

            const sizeSelect = newRow.querySelector('.js-size-select');
            if (sizeSelect) {
                sizeSelect.innerHTML = '<option value="">Không chọn kích cỡ</option>';
            }

            container.appendChild(newRow);
            syncOrderItemNames();
        }

        document.addEventListener('change', function (event) {
            if (event.target && event.target.matches('.js-product-select')) {
                const row = event.target.closest('.order-item-row');
                const sizeSelect = row ? row.querySelector('.js-size-select') : null;
                buildSizeOptions(sizeSelect, event.target.value);
            }
        });

        document.addEventListener('click', function (event) {
            if (event.target && event.target.id === 'add-order-item') {
                createOrderItemRow();
            }

            if (event.target && event.target.matches('.btn-remove-order-item')) {
                const row = event.target.closest('.order-item-row');
                const container = document.getElementById('order-items-container');
                if (!row || !container) return;
                row.remove();
                syncOrderItemNames();
            }
        });

        document.addEventListener('DOMContentLoaded', function () {
            syncOrderItemNames();

            document.querySelectorAll('.js-product-select').forEach((select) => {
                const row = select.closest('.order-item-row');
                const sizeSelect = row ? row.querySelector('.js-size-select') : null;
                buildSizeOptions(sizeSelect, select.value);
            });

            if (shouldOpenAddItemModal) {
                if (typeof openModal === 'function') {
                    openModal('add-item-modal');
                }
            }
        });

        (function () {
            const methodSelect = document.getElementById('table-payment-method-select');
            const actions = document.getElementById('table-payment-actions');
            if (!actions) return;

            const paid = actions.dataset.paid === '1';
            const cashAction = document.getElementById('table-cash-action');
            const transferAction = document.getElementById('table-transfer-action');
            const hint = document.getElementById('table-payment-hint');
            const paidNote = document.getElementById('table-payment-paid');

            function updateActions() {
                if (paid) {
                    if (paidNote) paidNote.style.display = 'block';
                    if (cashAction) cashAction.style.display = 'none';
                    if (transferAction) transferAction.style.display = 'none';
                    if (hint) hint.style.display = 'none';
                    return;
                }

                const method = methodSelect ? methodSelect.value : '';
                if (cashAction) cashAction.style.display = method === 'tiền mặt' ? 'block' : 'none';
                if (transferAction) transferAction.style.display = method === 'chuyển khoản' ? 'block' : 'none';
                if (hint) hint.style.display = method ? 'none' : 'block';
                if (paidNote) paidNote.style.display = 'none';
            }

            updateActions();

            if (methodSelect) {
                methodSelect.addEventListener('change', updateActions);
            }
        })();

        function generatePayOSQrManager(orderCode) {
            const emailInput = document.querySelector('input[name="email_khach_hang"]');
            PayOSPayment.start({
                orderCode: orderCode,
                source: 'manager',
                email: emailInput ? emailInput.value : '',
                button: document.getElementById('btn-generate-payos-manager'),
                iframe: document.getElementById('payos-qr-iframe-manager'),
                container: document.getElementById('payos-qr-container-manager'),
                successText: document.getElementById('payos-success-text-manager'),
                onPaid: function () { window.location.reload(); }
            });
        }
    </script>

@endpush