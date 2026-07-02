{{--
    Bộ tăng cường cho form "Thêm món" (dùng chung cho: quản lý bàn ăn - chi tiết bàn,
    quản lý đơn hàng - tạo đơn & sửa đơn).

    Tính năng:
    - Lọc sản phẩm theo danh mục (không chọn = hiện tất cả; chọn = chỉ danh mục đó).
    - Chọn nhiệt độ (nóng/lạnh) theo từng món, gộp vào ghi chú món dạng "(nóng) ghi chú".
    - Ô danh mục & sản phẩm dạng "clearable": khi đã chọn thì mũi tên đổi thành dấu (x)
      để bỏ chọn nhanh; bấm vào ô để mở chọn giá trị khác.

    Yêu cầu markup mỗi dòng món (.order-item-row):
    - <select class="js-category-select"> bọc trong .clearable-select
    - <select class="js-product-select"> bọc trong .clearable-select, mỗi option có data-danh-muc
    - <select class="js-temp-select">
    - input [data-field="ghi_chu_mon"]
    Cần có <script id="order-product-map-data"> chứa map sản phẩm (danh_muc_id, sizes, temps).
--}}
<style>
    .clearable-select { position: relative; }
    .clearable-select > select { width: 100%; }
    .clearable-select .clearable-clear {
        display: none !important;
    }
</style>
<script>
(function () {
    let productMap = {};
    function loadProductMap() {
        const mapEl = document.getElementById('order-product-map-data');
        try { productMap = mapEl ? JSON.parse(mapEl.textContent || '{}') : {}; } catch (e) { productMap = {}; }
    }

    const TEMP_RE = /^\(\s*(nóng|lạnh)\s*\)\s*/i;

    function getInfo(productId) {
        if (productId === '' || productId == null) return null;
        return productMap[String(productId)] || productMap[productId] || null;
    }

    function capitalize(str) {
        return str ? str.charAt(0).toUpperCase() + str.slice(1) : str;
    }

    function stripTemp(note) {
        return (note || '').replace(TEMP_RE, '');
    }

    function refreshClearable(select) {
        const wrap = select.closest('.clearable-select');
        if (!wrap) return;
        wrap.classList.toggle('has-value', !!(select.value && select.value !== ''));
    }

    function buildTempOptions(row, productId) {
        const tempSelect = row.querySelector('.js-temp-select');
        if (!tempSelect) return;

        const info = getInfo(productId);
        const temps = info && Array.isArray(info.temps) ? info.temps : [];
        const prev = tempSelect.value;

        let html = '<option value="">Không chọn nhiệt độ</option>';
        temps.forEach(function (t) {
            html += '<option value="' + t + '">' + capitalize(t) + '</option>';
        });
        tempSelect.innerHTML = html;

        if (prev && temps.indexOf(prev) !== -1) {
            tempSelect.value = prev;
        }
        tempSelect.disabled = temps.length === 0;
    }

    function applyCategoryFilter(row) {
        const catSelect = row.querySelector('.js-category-select');
        const prodSelect = row.querySelector('.js-product-select');
        if (!prodSelect) return;

        const cat = catSelect ? catSelect.value : '';
        let needReset = false;

        prodSelect.querySelectorAll('option').forEach(function (opt) {
            if (!opt.value) return; // luôn giữ placeholder
            const matches = !cat || opt.getAttribute('data-danh-muc') === cat;
            opt.hidden = !matches;
            if (!matches && opt.selected) needReset = true;
        });

        if (needReset) {
            prodSelect.value = '';
            refreshClearable(prodSelect);
            // báo cho các listener khác (build size của view) + dựng lại nhiệt độ
            prodSelect.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }

    function initRow(row) {
        const prodSelect = row.querySelector('.js-product-select');
        const tempSelect = row.querySelector('.js-temp-select');
        const noteInput = row.querySelector('[data-field="ghi_chu_mon"]');

        if (prodSelect) buildTempOptions(row, prodSelect.value);

        // Tách "(nóng)/(lạnh)" sẵn có trong ghi chú ra ô nhiệt độ (luồng sửa đơn).
        if (tempSelect && noteInput) {
            const m = (noteInput.value || '').match(TEMP_RE);
            if (m) {
                const t = m[1].toLowerCase();
                const hasOpt = Array.prototype.some.call(tempSelect.options, function (o) { return o.value === t; });
                if (hasOpt) {
                    tempSelect.value = t;
                    noteInput.value = stripTemp(noteInput.value).trim();
                }
            }
        }

        row.querySelectorAll('.clearable-select > select').forEach(refreshClearable);
        applyCategoryFilter(row);
    }

    function resetClonedRow(row) {
        const tempSelect = row.querySelector('.js-temp-select');
        if (tempSelect) {
            tempSelect.innerHTML = '<option value="">Không chọn nhiệt độ</option>';
            tempSelect.disabled = true;
        }
        const prodSelect = row.querySelector('.js-product-select');
        if (prodSelect) {
            prodSelect.querySelectorAll('option').forEach(function (o) { o.hidden = false; });
        }
        row.querySelectorAll('.clearable-select > select').forEach(refreshClearable);
    }

    function mergeTempsInForm(form) {
        form.querySelectorAll('.order-item-row').forEach(function (row) {
            const noteInput = row.querySelector('[data-field="ghi_chu_mon"]');
            if (!noteInput) return;
            const tempSelect = row.querySelector('.js-temp-select');
            // Sản phẩm không có lựa chọn nhiệt độ: giữ nguyên ghi chú người dùng nhập.
            if (!tempSelect || tempSelect.disabled) return;
            const base = stripTemp(noteInput.value).trim();
            const temp = tempSelect.value;
            noteInput.value = temp ? (base ? '(' + temp + ') ' + base : '(' + temp + ')') : base;
        });
    }

    document.addEventListener('change', function (e) {
        const t = e.target;
        if (!t || !t.matches) return;

        if (t.matches('.js-category-select')) {
            refreshClearable(t);
            const row = t.closest('.order-item-row');
            if (row) applyCategoryFilter(row);
        } else if (t.matches('.js-product-select')) {
            refreshClearable(t);
            const row = t.closest('.order-item-row');
            if (row) buildTempOptions(row, t.value);
        }
    });

    document.addEventListener('click', function (e) {
        const btn = e.target.closest ? e.target.closest('.clearable-clear') : null;
        if (!btn) return;
        e.preventDefault();
        const wrap = btn.closest('.clearable-select');
        const select = wrap ? wrap.querySelector('select') : null;
        if (!select) return;
        select.value = '';
        select.dispatchEvent(new Event('change', { bubbles: true }));
    });

    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (form && form.querySelector && form.querySelector('.order-item-row')) {
            mergeTempsInForm(form);
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        loadProductMap();
        document.querySelectorAll('.order-item-row').forEach(initRow);

        ['order-items-container', 'edit-order-items-container'].forEach(function (id) {
            const container = document.getElementById(id);
            if (!container) return;
            const mo = new MutationObserver(function (mutations) {
                mutations.forEach(function (mut) {
                    mut.addedNodes.forEach(function (node) {
                        if (node.nodeType === 1 && node.classList && node.classList.contains('order-item-row')) {
                            resetClonedRow(node);
                        }
                    });
                });
            });
            mo.observe(container, { childList: true });
        });
    });
})();
</script>
