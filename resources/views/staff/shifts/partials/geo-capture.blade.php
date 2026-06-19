@if(!empty($geoRequired))
    <div class="geo-backdrop" id="geo-modal">
        <div class="geo-modal">
            <div class="geo-icon">📍</div>
            <h2>Cho phép truy cập vị trí</h2>
            <p>Để chấm công, ứng dụng cần xác minh bạn đang ở tại quán. Bấm nút bên dưới rồi chọn <strong>"Cho phép"</strong> ở hộp thoại của trình duyệt.</p>
            <div id="geo-modal-status" class="geo-status" style="display:none;"></div>
            <button type="button" id="geo-allow-btn" class="btn btn-success" style="width:100%; margin-bottom:8px;">Cho phép vị trí &amp; chấm công</button>
            <button type="button" id="geo-close-btn" class="btn btn-secondary" style="width:100%;">Đóng</button>
        </div>
    </div>

    <style>
        .geo-backdrop {
            position: fixed; inset: 0; background: rgba(0,0,0,.5); display: none;
            align-items: center; justify-content: center; padding: 16px; z-index: 1000;
        }
        .geo-backdrop.open { display: flex; }
        .geo-modal {
            width: 100%; max-width: 380px; background: #fff; border-radius: 14px; padding: 22px;
            box-shadow: 0 12px 40px rgba(0,0,0,.25);
        }
        .geo-modal h2 { font-size: 17px; margin: 0 0 8px; }
        .geo-modal p { font-size: 13px; color: #555; line-height: 1.6; margin: 0 0 16px; }
        .geo-icon { font-size: 34px; text-align: center; margin-bottom: 6px; }
        .geo-status { font-size: 13px; margin-bottom: 14px; padding: 10px 12px; border-radius: 10px;
            background: #fde8e8; color: #b42318; line-height: 1.5; }
    </style>

    @push('scripts')
        <script>
            (function () {
                var modal = document.getElementById('geo-modal');
                var modalStatus = document.getElementById('geo-modal-status');
                var allowBtn = document.getElementById('geo-allow-btn');
                var closeBtn = document.getElementById('geo-close-btn');
                var pendingForm = null;

                function showModalStatus(msg) {
                    modalStatus.style.display = 'block';
                    modalStatus.textContent = msg;
                }
                function openModal() { modalStatus.style.display = 'none'; modal.classList.add('open'); }
                function closeModal() { modal.classList.remove('open'); }

                // Chặn submit các form chấm công để xin vị trí trước.
                document.querySelectorAll('form.js-geo-form').forEach(function (form) {
                    form.addEventListener('submit', function (e) {
                        if (form.dataset.geoReady === '1') { return; } // đã có toạ độ → cho gửi
                        e.preventDefault();
                        pendingForm = form;
                        allowBtn.disabled = false;
                        openModal();
                    });
                });

                allowBtn.addEventListener('click', function () {
                    if (!pendingForm) { return; }
                    if (!('geolocation' in navigator)) {
                        showModalStatus('Thiết bị/trình duyệt không hỗ trợ định vị nên không thể chấm công.');
                        return;
                    }
                    allowBtn.disabled = true;
                    showModalStatus('Đang lấy vị trí...');

                    navigator.geolocation.getCurrentPosition(
                        function (pos) {
                            pendingForm.querySelector('input[name=latitude]').value = pos.coords.latitude;
                            pendingForm.querySelector('input[name=longitude]').value = pos.coords.longitude;
                            pendingForm.dataset.geoReady = '1';
                            closeModal();
                            pendingForm.submit();
                        },
                        function (err) {
                            allowBtn.disabled = false;
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
    @endpush
@endif
