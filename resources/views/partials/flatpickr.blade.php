{{--
    Ép mọi <input type="date"> hiển thị theo định dạng ngày/tháng/năm (d/m/Y)
    bất kể locale trình duyệt. flatpickr tạo một ô text hiển thị d/m/Y và chuyển
    input gốc thành hidden (giữ nguyên name + giá trị Y-m-d để gửi lên server),
    đồng thời copy required/min/max sang ô hiển thị nên không phá vỡ form sẵn có.
--}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css">
<style>
    /* Thêm icon lịch vào ô input date do flatpickr tạo ra.
       Màu icon (#302617) trùng với màu chữ ngày để đồng bộ, không còn màu cam. */
    .custom-flatpickr-input {
        color: #302617 !important;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='18' height='18' viewBox='0 0 24 24' fill='none' stroke='%23302617' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect x='3' y='4' width='18' height='18' rx='2' ry='2'%3E%3C/rect%3E%3Cline x1='16' y1='2' x2='16' y2='6'%3E%3C/line%3E%3Cline x1='8' y1='2' x2='8' y2='6'%3E%3C/line%3E%3Cline x1='3' y1='10' x2='21' y2='10'%3E%3C/line%3E%3C/svg%3E") !important;
        background-repeat: no-repeat !important;
        background-position: right 14px center !important;
        background-size: 18px !important;
        padding-right: 40px !important;
        cursor: pointer;
    }

    /* Icon đồng hồ cho ô chọn giờ (flatpickr time, hiển thị 24h) */
    .custom-flatpickr-time-input {
        color: #302617 !important;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='18' height='18' viewBox='0 0 24 24' fill='none' stroke='%23302617' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='12' cy='12' r='10'%3E%3C/circle%3E%3Cpolyline points='12 6 12 12 16 14'%3E%3C/polyline%3E%3C/svg%3E") !important;
        background-repeat: no-repeat !important;
        background-position: right 14px center !important;
        background-size: 18px !important;
        padding-right: 40px !important;
        cursor: pointer;
    }

    /* Thay ô gõ năm bằng dropdown chọn năm (ẩn ô số mặc định của flatpickr) */
    .flatpickr-current-month .numInputWrapper {
        display: none !important;
    }
    .flatpickr-current-month .flatpickr-yearDropdown {
        background: transparent;
        border: none;
        font-size: inherit;
        font-weight: 600;
        color: inherit;
        cursor: pointer;
        padding: 0 4px;
        outline: none;
        -webkit-appearance: menulist;
        appearance: menulist;
    }
    /* Đồng bộ màu chủ đạo của lịch với tông nâu cà phê (ngày đang chọn) */
    .flatpickr-day.selected,
    .flatpickr-day.selected:hover,
    .flatpickr-day.startRange,
    .flatpickr-day.endRange {
        background: #302617 !important;
        border-color: #302617 !important;
    }
    .flatpickr-day.today {
        border-color: #c49a6c;
    }
</style>
<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/l10n/vn.js"></script>
<script>
    (function () {
        // Tạo dropdown chọn NĂM thay cho ô gõ số mặc định của flatpickr
        function buildYearDropdown(instance) {
            var monthWrap = instance.currentMonthElement && instance.currentMonthElement.parentNode;
            if (!monthWrap || monthWrap.querySelector('.flatpickr-yearDropdown')) return;

            var thisYear = new Date().getFullYear();
            var minYear = instance.config.minDate ? instance.config.minDate.getFullYear() : thisYear - 100;
            var maxYear = instance.config.maxDate ? instance.config.maxDate.getFullYear() : thisYear + 10;

            var select = document.createElement('select');
            select.className = 'flatpickr-yearDropdown';
            for (var y = maxYear; y >= minYear; y--) {
                var opt = document.createElement('option');
                opt.value = y;
                opt.textContent = y;
                select.appendChild(opt);
            }
            select.value = instance.currentYear;
            select.addEventListener('change', function () {
                instance.changeYear(parseInt(this.value, 10));
            });
            monthWrap.appendChild(select);
            instance._yearDropdown = select;
        }

        function syncYearDropdown(instance) {
            if (instance._yearDropdown) {
                instance._yearDropdown.value = instance.currentYear;
            }
        }

        // Mask cho ô giờ: luôn giữ dấu ":", người dùng chỉ gõ/xóa phần giờ và phút.
        // Gõ digit -> tự định dạng HH:MM (vd "1430" -> "14:30"); xóa dấu ":" sẽ tự khôi phục.
        function attachTimeMask(instance) {
            var input = instance.altInput;
            if (!input || input._timeMask) return;
            input._timeMask = true;
            input.setAttribute('maxlength', '5');
            input.setAttribute('inputmode', 'numeric');
            input.setAttribute('placeholder', '--:--');

            input.addEventListener('input', function () {
                var digits = input.value.replace(/\D/g, '').slice(0, 4);
                // Kẹp giờ (00-23) và phút (00-59) ngay khi đủ chữ số
                if (digits.length >= 1 && parseInt(digits[0], 10) > 2) digits = '0' + digits;
                if (digits.length >= 2 && parseInt(digits.slice(0, 2), 10) > 23) digits = '23' + digits.slice(2);
                if (digits.length >= 3 && parseInt(digits[2], 10) > 5) digits = digits.slice(0, 2) + '5' + digits.slice(3);

                var out = digits;
                if (digits.length > 2) {
                    out = digits.slice(0, 2) + ':' + digits.slice(2);
                }
                input.value = out;
            });
        }

        // Khi đóng lịch/đồng hồ, ép parse lại giá trị người dùng gõ tay trong ô hiển thị
        // (mặc định flatpickr chỉ commit khi nhấn Enter, không commit khi click ra ngoài).
        function commitTypedValue(selectedDates, dateStr, instance) {
            if (!instance.config.allowInput || !instance.altInput) return;
            var typed = instance.altInput.value.trim();
            if (typed) {
                instance.setDate(typed, true, instance.config.altFormat);
            }
        }

        function initDatePickers(root) {
            if (typeof flatpickr === 'undefined') return;
            if (flatpickr.l10ns && flatpickr.l10ns.vn) {
                // Hiển thị "Tháng 1".."Tháng 12" thay vì "Tháng một".."Tháng mười hai"
                var monthsVi = [];
                for (var m = 1; m <= 12; m++) { monthsVi.push('Tháng ' + m); }
                flatpickr.l10ns.vn.months = { shorthand: monthsVi, longhand: monthsVi };
                flatpickr.localize(flatpickr.l10ns.vn);
            }
            (root || document).querySelectorAll('input[type="date"]').forEach(function (el) {
                if (el._flatpickr) return; // đã khởi tạo
                
                // Lấy class cũ của input (ví dụ: form-control w-auto) và thêm class nhận diện icon
                var existingClasses = el.className || '';
                var newClasses = existingClasses + ' custom-flatpickr-input';
                
                flatpickr(el, {
                    dateFormat: 'Y-m-d',   // giá trị gửi lên server (giữ nguyên như cũ)
                    altInput: true,
                    altInputClass: newClasses,
                    altFormat: 'd/m/Y',    // định dạng hiển thị cho người dùng
                    allowInput: true,
                    minDate: el.getAttribute('min') || null,
                    maxDate: el.getAttribute('max') || null,
                    onReady: function (selectedDates, dateStr, instance) {
                        buildYearDropdown(instance);
                    },
                    onYearChange: function (selectedDates, dateStr, instance) {
                        syncYearDropdown(instance);
                    },
                    // Commit giá trị gõ tay khi click ra ngoài (không cần nhấn Enter)
                    onClose: commitTypedValue,
                });
            });

            // Ô chọn giờ: ép hiển thị 24h thay vì AM/PM của trình duyệt
            (root || document).querySelectorAll('input[type="time"]').forEach(function (el) {
                if (el._flatpickr) return; // đã khởi tạo

                var newClasses = (el.className || '') + ' custom-flatpickr-time-input';

                flatpickr(el, {
                    enableTime: true,
                    noCalendar: true,
                    dateFormat: 'H:i',    // 24h, giữ nguyên giá trị HH:mm gửi lên server
                    time_24hr: true,
                    altInput: true,
                    altInputClass: newClasses,
                    altFormat: 'H:i',
                    allowInput: true,
                    minuteIncrement: 1,
                    onReady: function (selectedDates, dateStr, instance) {
                        attachTimeMask(instance);
                    },
                    // Commit giá trị gõ tay khi click ra ngoài (không cần nhấn Enter)
                    onClose: commitTypedValue,
                });
            });
        }

        // Cho phép trang khác gọi lại nếu chèn input ngày bằng JS sau khi tải.
        window.initDatePickers = initDatePickers;

        document.addEventListener('DOMContentLoaded', function () { initDatePickers(document); });
    })();
</script>
