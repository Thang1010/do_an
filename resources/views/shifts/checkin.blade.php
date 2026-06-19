<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Xác nhận chấm công</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #8B4513;
            --secondary: #D2691E;
            --text-dark: #2c2c2c;
            --text-light: #666;
            --border: #ece6dd;
            --success: #166534;
            --warning: #92400E;
            --danger: #B91C1C;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            min-height: 100vh; display: flex; align-items: center; justify-content: center;
            font-family: 'Roboto', -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif;
            background: linear-gradient(135deg, #8B4513 0%, #D2691E 100%);
            color: var(--text-dark); padding: 18px;
        }
        .card {
            width: 100%; max-width: 420px; background: #fff; border-radius: 20px;
            padding: 28px 24px; box-shadow: 0 10px 40px rgba(0,0,0,.18);
        }
        h1 { font-family: 'Playfair Display', serif; font-size: 22px; font-weight: 700; margin-bottom: 4px; }
        .sub { color: var(--text-light); font-size: 13px; margin-bottom: 18px; }
        .badge {
            display: inline-block; padding: 5px 14px; border-radius: 999px; font-size: 13px;
            font-weight: 600; margin-bottom: 16px;
        }
        .badge-in { background: rgba(22,101,52,.12); color: var(--success); }
        .badge-out { background: rgba(146,64,14,.12); color: var(--warning); }
        .badge-done { background: rgba(139,69,19,.1); color: var(--primary); }
        .row { display: flex; justify-content: space-between; gap: 12px; padding: 10px 0; border-bottom: 1px solid var(--border); font-size: 14px; }
        .row .label { color: var(--text-light); }
        .row .value { font-weight: 600; text-align: right; }
        .status { font-size: 13px; margin: 16px 0; padding: 11px 13px; border-radius: 10px; line-height: 1.55; }
        .status-info { background: rgba(139,69,19,.08); color: #6b4a2b; }
        .status-err  { background: #fdecea; color: var(--danger); }
        button {
            width: 100%; padding: 14px; border: 2px solid transparent; border-radius: 12px;
            font-size: 16px; font-weight: 700; cursor: pointer; margin-top: 8px;
            font-family: 'Roboto', sans-serif; transition: all .25s ease;
        }
        button[disabled] { opacity: .55; cursor: not-allowed; }
        .btn-primary { background: var(--primary); color: #fff; border-color: var(--primary); }
        .btn-primary:hover:not([disabled]) { background: var(--secondary); border-color: var(--secondary); }
        .btn-ghost { background: #f3f0ea; color: var(--text-dark); border-color: #f3f0ea; }
        .btn-ghost:hover { background: #e7e0d4; }
        .muted { color: var(--text-light); font-size: 12px; text-align: center; margin-top: 14px; }

        /* Modal xin quyền vị trí */
        .geo-backdrop {
            position: fixed; inset: 0; background: rgba(48,38,28,.55); display: none;
            align-items: center; justify-content: center; padding: 16px; z-index: 50;
        }
        .geo-backdrop.open { display: flex; }
        .geo-modal { width: 100%; max-width: 380px; background: #fff; border-radius: 18px; padding: 24px; box-shadow: 0 12px 40px rgba(0,0,0,.25); }
        .geo-modal h2 { font-family: 'Playfair Display', serif; font-size: 18px; margin-bottom: 8px; }
        .geo-modal p { font-size: 13px; color: var(--text-light); line-height: 1.6; margin-bottom: 16px; }
        .geo-icon { font-size: 34px; text-align: center; margin-bottom: 6px; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Chấm công ca {{ $shift->ten_ca }}</h1>
        <p class="sub">Xin chào {{ $actorName ?? 'bạn' }}</p>

        @if($action === 'checkin')
            <span class="badge badge-in">Sắp chấm công VÀO ca</span>
        @elseif($action === 'checkout')
            <span class="badge badge-out">Sắp chấm công RA ca</span>
        @else
            <span class="badge badge-done">Đã hoàn thành chấm công</span>
        @endif

        <div class="row"><span class="label">Ngày làm</span><span class="value">{{ $shift->ngay_lam ? \Carbon\Carbon::parse($shift->ngay_lam)->format('d/m/Y') : '—' }}</span></div>
        <div class="row"><span class="label">Giờ ca</span><span class="value">{{ \Carbon\Carbon::parse($shift->gio_bat_dau)->format('H:i') }} - {{ \Carbon\Carbon::parse($shift->gio_ket_thuc)->format('H:i') }}</span></div>
        @if($attendance->cham_cong_vao)
            <div class="row"><span class="label">Đã vào lúc</span><span class="value">{{ \Carbon\Carbon::parse($attendance->cham_cong_vao)->format('H:i d/m') }}</span></div>
        @endif

        @if($action === 'done')
            <div class="status status-info">Bạn đã chấm công vào và ra ca này. Không cần thao tác thêm.</div>
            <p class="muted">Bạn có thể đóng trang này.</p>
        @else
            <div id="page-status" class="status status-err" style="display:none;"></div>

            <form id="checkin-form" method="POST" action="{{ $submitUrl }}" data-geo-required="{{ !empty($geoRequired) ? '1' : '0' }}">
                @csrf
                <input type="hidden" name="latitude" id="latitude">
                <input type="hidden" name="longitude" id="longitude">
                <button type="submit" id="submit-btn" class="btn-primary">
                    {{ $action === 'checkout' ? 'Xác nhận chấm công RA' : 'Xác nhận chấm công VÀO' }}
                </button>
            </form>
            <p class="muted">Hãy đảm bảo bạn đang có mặt tại quán khi chấm công.</p>
        @endif
    </div>

    @if($action !== 'done')
    <div class="geo-backdrop" id="geo-modal">
        <div class="geo-modal">
            <div class="geo-icon">📍</div>
            <h2>Cho phép truy cập vị trí</h2>
            <p>Để chấm công, ứng dụng cần xác minh bạn đang ở tại quán. Bấm nút bên dưới rồi chọn <strong>"Cho phép"</strong> ở hộp thoại của trình duyệt.</p>
            <div id="modal-status" class="status status-err" style="display:none; margin-top:0;"></div>
            <button type="button" id="allow-btn" class="btn-primary">Cho phép vị trí &amp; chấm công</button>
            <button type="button" id="close-btn" class="btn-ghost">Đóng</button>
        </div>
    </div>

    <script>
        (function () {
            var form = document.getElementById('checkin-form');
            var geoRequired = form.dataset.geoRequired === '1';
            var latInput = document.getElementById('latitude');
            var lngInput = document.getElementById('longitude');
            var pageStatus = document.getElementById('page-status');

            var modal = document.getElementById('geo-modal');
            var modalStatus = document.getElementById('modal-status');
            var allowBtn = document.getElementById('allow-btn');
            var closeBtn = document.getElementById('close-btn');

            var geoReady = false;
            var deniedOnce = false;

            function showPageStatus(msg) {
                pageStatus.style.display = 'block';
                pageStatus.textContent = msg;
            }
            function showModalStatus(msg) {
                modalStatus.style.display = 'block';
                modalStatus.textContent = msg;
            }
            function openModal() {
                modalStatus.style.display = 'none';
                modal.classList.add('open');
            }
            function closeModal() { modal.classList.remove('open'); }

            // Không bắt buộc GPS → gửi bình thường.
            if (!geoRequired) { return; }

            form.addEventListener('submit', function (e) {
                if (geoReady) { return; } // đã có toạ độ → cho gửi
                e.preventDefault();
                if (deniedOnce) {
                    showPageStatus('Bạn chưa cho phép truy cập vị trí nên không thể chấm công. Hãy bấm "Cho phép vị trí" và chọn Cho phép.');
                }
                openModal();
            });

            allowBtn.addEventListener('click', function () {
                if (!('geolocation' in navigator)) {
                    showModalStatus('Thiết bị/trình duyệt không hỗ trợ định vị nên không thể chấm công.');
                    return;
                }
                allowBtn.disabled = true;
                showModalStatus('Đang lấy vị trí...');

                navigator.geolocation.getCurrentPosition(
                    function (pos) {
                        latInput.value = pos.coords.latitude;
                        lngInput.value = pos.coords.longitude;
                        geoReady = true;
                        closeModal();
                        form.submit();
                    },
                    function (err) {
                        allowBtn.disabled = false;
                        deniedOnce = true;
                        var msg;
                        switch (err && err.code) {
                            case 1: // PERMISSION_DENIED — chưa cấp quyền cho trang
                                msg = 'Bạn chưa cấp quyền vị trí cho trang (hoặc đã từ chối). Hãy chọn "Cho phép" khi được hỏi. iPhone: nếu không hiện hỏi, vào Cài đặt → Safari → Vị trí đặt lại "Hỏi". Nếu mở link trong app Zalo/Messenger, hãy mở bằng Safari/Chrome.';
                                break;
                            case 2: // POSITION_UNAVAILABLE — thường do GPS/định vị tắt
                                msg = 'Chưa bật định vị (GPS). Hãy bật Dịch vụ định vị của máy (iPhone: Cài đặt → Quyền riêng tư & Bảo mật → Dịch vụ định vị) rồi thử lại.';
                                break;
                            case 3: // TIMEOUT
                                msg = 'Lấy vị trí quá lâu. Hãy ra chỗ thoáng (gần cửa sổ/ngoài trời) rồi bấm thử lại.';
                                break;
                            default:
                                msg = 'Không lấy được vị trí. Vui lòng thử lại.';
                        }
                        showModalStatus(msg);
                    },
                    { enableHighAccuracy: true, timeout: 15000, maximumAge: 30000 }
                );
            });

            closeBtn.addEventListener('click', closeModal);
        })();
    </script>
    @endif
</body>
</html>
