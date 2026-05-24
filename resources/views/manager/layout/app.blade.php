<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Quản lý') — XM Coffee</title>
    <meta name="description" content="Trang quản lý hệ thống XM Coffee">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Playfair+Display:wght@400;600;700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('css/manager-layout.css') }}">

    @stack('styles')
</head>
<body>

<!-- Sidebar Overlay (mobile) -->
<div id="sidebar-overlay" onclick="toggleSidebar()"></div>

<!-- =============== SIDEBAR =============== -->
<aside id="sidebar">
    <!-- Brand -->
    <div class="sidebar-brand">
        <a href="{{ route('home') }}" class="sidebar-brand-logo">
            <div class="sidebar-brand-img">
                <img src="{{ asset('images/logo.png') }}" alt="Logo"
                     onerror="this.parentElement.innerHTML='XM'"/>
            </div>
            <div>
                <div class="sidebar-brand-text">XM Coffee</div>
                <div class="sidebar-brand-sub">Hệ thống quản lý</div>
            </div>
        </a>
    </div>

    <!-- Navigation -->
    <nav class="sidebar-nav">
        @php
            $isStoreOwner = auth()->check() && auth()->user()->vai_tro === 'chủ cửa hàng';
            $inventoryPurposes = \Illuminate\Support\Facades\Cache::remember('manager.inventory.purposes', now()->addMinutes(5), function () {
                return \App\Models\NguyenLieu::query()
                    ->whereNotNull('muc_dich_su_dung')
                    ->where('muc_dich_su_dung', '!=', '')
                    ->orderBy('muc_dich_su_dung')
                    ->pluck('muc_dich_su_dung')
                    ->unique()
                    ->values()
                    ->toArray();
            });
            $hasUnclassifiedInventory = \Illuminate\Support\Facades\Cache::remember('manager.inventory.unclassified', now()->addMinutes(5), function () {
                return \App\Models\NguyenLieu::query()
                    ->where(function ($q) {
                        $q->whereNull('muc_dich_su_dung')->orWhere('muc_dich_su_dung', '');
                    })
                    ->exists();
            });
            $currentInventoryPurpose = request('muc_dich_su_dung', '');
        @endphp

        <!-- TỔNG QUAN -->
        <div class="nav-section-label">Tổng quan</div>
        <a href="{{ route('manager.dashboard') }}"
           class="nav-item {{ request()->routeIs('manager.dashboard') ? 'active' : '' }}">
            Bảng điều khiển
        </a>

        <!-- KINH DOANH -->
        <div class="nav-section-label">Kinh doanh</div>

        <a href="{{ route('manager.orders.index') }}"
           class="nav-item {{ request()->routeIs('manager.orders*') ? 'active' : '' }}">
            Quản lý đơn hàng
        </a>

        <a href="{{ route('manager.products.index') }}"
           class="nav-item {{ request()->routeIs('manager.products*') ? 'active' : '' }}">
            Quản lý sản phẩm
        </a>

        <a href="{{ route('manager.categories.index') }}"
           class="nav-item {{ request()->routeIs('manager.categories*') ? 'active' : '' }}">
            Quản lý danh mục
        </a>

        <a href="{{ route('manager.tables.index') }}"
           class="nav-item {{ request()->routeIs('manager.tables*') ? 'active' : '' }}">
            Quản lý bàn ăn
        </a>

        <a href="{{ route('manager.shift-close.index') }}"
           class="nav-item {{ request()->routeIs('manager.shift-close*') ? 'active' : '' }}">
            Chốt ca
        </a>

        <a href="{{ route('manager.vouchers.index') }}"
           class="nav-item {{ request()->routeIs('manager.vouchers*') ? 'active' : '' }}">
            Voucher / Khuyến mãi
        </a>

        <!-- NHÂN SỰ -->
        <div class="nav-section-label">Nhân sự</div>

        <a href="{{ route('manager.positions.index') }}"
           class="nav-item {{ request()->routeIs('manager.positions*') ? 'active' : '' }}">
            Quản lý chức vụ
        </a>

        <div class="nav-item nav-group-toggle {{ request()->routeIs('manager.users*') ? 'active open' : '' }}"
             onclick="toggleMenu('menu-users', this)">
            Quản lý người dùng
        </div>
        @php
            $canManageAdmins = $isStoreOwner;
        @endphp
        <div class="nav-submenu {{ request()->routeIs('manager.users*') ? 'open' : '' }}" id="menu-users">
            <a href="{{ route('manager.users.customers') }}"
               class="nav-item {{ request()->routeIs('manager.users.customers') ? 'active' : '' }}">
                Khách hàng
            </a>
                <a href="{{ route('manager.users.staffs') }}"
                    class="nav-item {{ request()->routeIs('manager.users.staff') || request()->routeIs('manager.users.staffs') ? 'active' : '' }}">
                Nhân viên
            </a>
            @if($canManageAdmins)
                <a href="{{ route('manager.users.admins') }}"
                    class="nav-item {{ request()->routeIs('manager.users.admins') ? 'active' : '' }}">
                     Quản lý
                </a>
            @endif
        </div>

        <a href="{{ route('manager.shifts.index') }}"
           class="nav-item {{ request()->routeIs('manager.shifts*') ? 'active' : '' }}">
            Quản lý ca làm việc
        </a>

        <!-- KHO / TÀI CHÍNH -->
        <div class="nav-section-label">Kho & Tài chính</div>

        <div class="nav-item nav-group-toggle {{ request()->routeIs('manager.ingredients*') ? 'active open' : '' }}"
             onclick="toggleMenu('menu-ingredients', this)">
            Quản lý nguyên liệu
        </div>
        <div class="nav-submenu {{ request()->routeIs('manager.ingredients*') ? 'open' : '' }}" id="menu-ingredients">
            <a href="{{ route('manager.ingredients.index') }}"
               class="nav-item {{ request()->routeIs('manager.ingredients.index') || request()->routeIs('manager.ingredients.create') || request()->routeIs('manager.ingredients.edit') ? 'active' : '' }}">
                Danh mục nguyên liệu
            </a>
            <a href="{{ route('manager.ingredients.requests.index') }}"
               class="nav-item {{ request()->routeIs('manager.ingredients.requests*') ? 'active' : '' }}">
                Yêu cầu chờ duyệt
            </a>
        </div>

        <div class="nav-item nav-group-toggle {{ request()->routeIs('manager.expenses*') || request()->routeIs('manager.salary*') ? 'active open' : '' }}"
             onclick="toggleMenu('menu-expenses', this)">
            Quản lý chi tiêu
        </div>
        <div class="nav-submenu {{ request()->routeIs('manager.expenses*') || request()->routeIs('manager.salary*') ? 'open' : '' }}" id="menu-expenses">
            <a href="{{ route('manager.expenses.index') }}"
               class="nav-item {{ request()->routeIs('manager.expenses*') ? 'active' : '' }}">
                Mua/Bán
            </a>
            <a href="{{ route('manager.salary.index') }}"
               class="nav-item {{ request()->routeIs('manager.salary*') ? 'active' : '' }}">
                Lương
            </a>
        </div>

        <div class="nav-item nav-group-toggle {{ request()->routeIs('manager.inventory*') ? 'active open' : '' }}"
             onclick="toggleMenu('menu-inventory', this)">
            Quản lý kho
        </div>
        <div class="nav-submenu {{ request()->routeIs('manager.inventory*') ? 'open' : '' }}" id="menu-inventory">
            @foreach($inventoryPurposes as $purpose)
                @php $purposeStr = is_array($purpose) ? implode(', ', $purpose) : (string) $purpose; @endphp
                <a href="{{ route('manager.inventory.index', ['muc_dich_su_dung' => $purposeStr]) }}"
                   class="nav-item {{ request()->routeIs('manager.inventory*') && $currentInventoryPurpose === $purposeStr ? 'active' : '' }}">
                    Quản lý kho {{ $purposeStr }}
                </a>
            @endforeach
            @if($hasUnclassifiedInventory)
                <a href="{{ route('manager.inventory.index', ['muc_dich_su_dung' => '__none__']) }}"
                   class="nav-item {{ request()->routeIs('manager.inventory*') && $currentInventoryPurpose === '__none__' ? 'active' : '' }}">
                    Kho chưa phân loại
                </a>
            @endif
            @if(empty($inventoryPurposes) && ! $hasUnclassifiedInventory)
                <a href="{{ route('manager.inventory.index') }}"
                   class="nav-item {{ request()->routeIs('manager.inventory*') ? 'active' : '' }}">
                    Quản lý kho
                </a>
            @endif
        </div>

        <!-- BÁO CÁO -->
        <div class="nav-section-label">Báo cáo</div>

        <div class="nav-item nav-group-toggle {{ request()->routeIs('manager.reports*') ? 'active open' : '' }}"
             onclick="toggleMenu('menu-reports', this)">
            Thống kê & Báo cáo
        </div>
        <div class="nav-submenu {{ request()->routeIs('manager.reports*') ? 'open' : '' }}" id="menu-reports">
            <a href="{{ route('manager.reports.revenue') }}"
               class="nav-item {{ request()->routeIs('manager.reports.revenue') ? 'active' : '' }}">
                Doanh thu
            </a>
            <a href="{{ route('manager.reports.orders') }}"
               class="nav-item">
                Đơn hàng
            </a>
            <a href="{{ route('manager.reports.products') }}"
               class="nav-item">
                Sản phẩm bán chạy
            </a>
            <a href="{{ route('manager.reports.staff') }}"
               class="nav-item">
                Hiệu suất nhân viên
            </a>
            <a href="{{ route('manager.reports.inventory') }}"
               class="nav-item">
                Tồn kho
            </a>
        </div>

    </nav>

    <!-- Sidebar Footer — User Info -->
    @php
        $currentUser = auth()->user();
        $displayUserName = $currentUser?->ho_ten ?? null;
        if (is_array($displayUserName)) {
            $displayUserName = implode(' ', array_filter($displayUserName, 'strlen'));
        }
        $displayUserName = is_scalar($displayUserName) && $displayUserName !== ''
            ? (string) $displayUserName
            : 'Quản lý';

        $displayUserRole = $currentUser?->vai_tro ?? null;
        if (is_array($displayUserRole)) {
            $displayUserRole = implode(', ', array_filter($displayUserRole, 'strlen'));
        }
        $displayUserRole = is_scalar($displayUserRole) && $displayUserRole !== ''
            ? (string) $displayUserRole
            : 'quản lý';

        $displayUserInitial = $displayUserName !== '' ? mb_substr($displayUserName, 0, 1) : 'Q';
    @endphp
    <div class="sidebar-footer">
        <div class="sidebar-user-menu-wrap" id="sidebar-user-menu-wrap">
            <button type="button" class="sidebar-user-trigger" onclick="toggleProfileMenu(event)">
                <div class="sidebar-user">
                    <div class="sidebar-user-avatar">
                        {{ $displayUserInitial }}
                    </div>
                    <div class="sidebar-user-info">
                        <div class="sidebar-user-name">
                            {{ $displayUserName }}
                        </div>
                        <div class="sidebar-user-role">{{ $displayUserRole }}</div>
                    </div>
                </div>
            </button>

            <div class="sidebar-user-menu" id="sidebar-user-menu">
                <a href="{{ route('manager.profile.edit') }}" class="sidebar-user-menu-item">Hồ sơ cá nhân</a>
                <form method="POST" action="{{ route('auth.logout') }}">
                    @csrf
                    <button type="submit" class="sidebar-user-menu-item sidebar-user-menu-logout">Đăng xuất</button>
                </form>
            </div>
        </div>
    </div>
</aside>

<!-- =============== MAIN WRAPPER =============== -->
<div id="main-wrapper">

    <!-- Header -->
    <header id="main-header">
        <button class="header-toggle" onclick="toggleSidebar()" aria-label="Menu">
            <span></span><span></span><span></span>
        </button>

        <div class="header-breadcrumb">
            @yield('breadcrumb', '<strong>Bảng điều khiển</strong>')
        </div>

        <div class="header-search">
            <input type="text" placeholder="Tìm kiếm nhanh...">
        </div>

        <div class="header-right">
            <!-- Notifications -->
            @php
                $unreadNotificationCount = auth()->check()
                    ? auth()->user()->unreadNotifications()->count()
                    : 0;
                $recentNotifications = auth()->check()
                    ? auth()->user()->notifications()->latest()->limit(8)->get()
                    : collect();
            @endphp
            <div class="notification-badge" id="notification-wrap">
                <button
                    class="header-btn"
                    id="notif-btn"
                    type="button"
                    onclick="toggleNotif(event)"
                    aria-label="Thông báo"
                >
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M18 8a6 6 0 10-12 0c0 7-3 9-3 9h18s-3-2-3-9"></path>
                        <path d="M13.73 21a2 2 0 01-3.46 0"></path>
                    </svg>
                    @if($unreadNotificationCount > 0)
                        <span class="badge">{{ min($unreadNotificationCount, 99) }}</span>
                    @endif
                </button>

                <div class="notification-dropdown" id="notif-dropdown">
                    <div class="notification-dropdown-header">
                        <span>Thông báo gần đây</span>
                        <form method="POST" action="{{ route('manager.notifications.read-all') }}">
                            @csrf
                            <button type="submit" class="notif-mark-all">Đọc tất cả</button>
                        </form>
                    </div>

                    <div class="notification-dropdown-list">
                        @forelse($recentNotifications as $notification)
                            @php
                                $data = (array) ($notification->data ?? []);
                                $titleRaw = $data['title'] ?? 'Thông báo hệ thống';
                                $messageRaw = $data['message'] ?? 'Bạn có thông báo mới.';
                                $title = is_array($titleRaw)
                                    ? implode(', ', array_filter($titleRaw, 'strlen'))
                                    : (is_scalar($titleRaw) ? (string) $titleRaw : 'Thông báo hệ thống');
                                $message = is_array($messageRaw)
                                    ? implode(', ', array_filter($messageRaw, 'strlen'))
                                    : (is_scalar($messageRaw) ? (string) $messageRaw : 'Bạn có thông báo mới.');
                            @endphp
                            <a href="{{ route('manager.notifications.open', $notification->id) }}"
                               class="notification-item {{ $notification->read_at ? '' : 'unread' }}">
                                <div class="notification-item-title">{{ $title }}</div>
                                <div class="notification-item-message">{{ $message }}</div>
                                <div class="notification-item-time">{{ optional($notification->created_at)->format('d/m H:i') }}</div>
                            </a>
                        @empty
                            <div class="notification-empty">Chưa có thông báo nào.</div>
                        @endforelse
                    </div>

                    <div class="notification-dropdown-footer">
                        <a href="{{ route('manager.notifications.index') }}">Xem tất cả thông báo</a>
                    </div>
                </div>
            </div>

            <!-- View site -->
            <a href="{{ route('home') }}" target="_blank" class="header-btn">
                Xem trang web
            </a>
        </div>
    </header>

    <!-- Alerts from session -->
    @if(session('success') || session('error') || session('warning'))
    <div style="padding: 12px 28px 0;">
        @if(session('success'))
            <div class="alert alert-success">{{ is_array(session('success')) ? implode(', ', session('success')) : session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-error">{{ is_array(session('error')) ? implode(', ', session('error')) : session('error') }}</div>
        @endif
        @if(session('warning'))
            <div class="alert alert-warning">{{ is_array(session('warning')) ? implode(', ', session('warning')) : session('warning') }}</div>
        @endif
    </div>
    @endif

    <!-- Validation errors -->
    @if($errors->any())
    <div style="padding: 12px 28px 0;">
        <div class="alert alert-error">
            @foreach($errors->all() as $error)
                <div>— {{ $error }}</div>
            @endforeach
        </div>
    </div>
    @endif

    @if(session('force_password_setup') && auth()->check())
    <div id="force-password-modal" class="force-password-modal" role="dialog" aria-modal="true" aria-label="Đặt mật khẩu XM COFFEE">
        <div class="force-password-modal__backdrop"></div>
        <div class="force-password-modal__panel">
            <div class="force-password-modal__title">Đặt mật khẩu XM COFFEE</div>
            <p class="force-password-modal__desc">Vui lòng đặt mật khẩu mới để hoàn tất đăng nhập.</p>
            @if($errors->any())
                <div class="force-password-modal__alert">
                    @foreach($errors->all() as $error)
                        <div>• {{ $error }}</div>
                    @endforeach
                </div>
            @endif
            <form method="POST" action="{{ route('auth.password.setup.post') }}">
                @csrf
                <div class="force-password-modal__field">
                    <label for="force-password">Mật khẩu mới</label>
                    <input id="force-password" type="password" name="password" autocomplete="new-password" placeholder="Nhập mật khẩu mới" required>
                </div>
                <div class="force-password-modal__field">
                    <label for="force-password-confirm">Nhập lại mật khẩu</label>
                    <input id="force-password-confirm" type="password" name="password_confirmation" autocomplete="new-password" placeholder="Nhập lại mật khẩu" required>
                </div>
                <div class="force-password-modal__hint">Mật khẩu tối thiểu 8 ký tự.</div>
                <button type="submit" class="force-password-modal__submit">Lưu mật khẩu</button>
            </form>
        </div>
    </div>
    @endif

    <!-- Page content -->
    <main id="main-content">
        @yield('content')
    </main>

</div>

<!-- =============== SCRIPTS =============== -->
<style>
    .force-password-modal {
        position: fixed;
        inset: 0;
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        padding: 20px;
    }
    .force-password-modal.is-open {
        display: flex;
    }
    .force-password-modal__backdrop {
        position: absolute;
        inset: 0;
        background: rgba(18, 12, 8, 0.7);
        backdrop-filter: blur(2px);
    }
    .force-password-modal__panel {
        position: relative;
        width: min(460px, 92vw);
        background: #1f1710;
        border: 1px solid rgba(141, 93, 93, 0.5);
        border-radius: 18px;
        padding: 28px 26px;
        box-shadow: 0 24px 60px rgba(0, 0, 0, 0.45);
        color: #f1f0ee;
        font-family: 'Outfit', sans-serif;
    }
    .force-password-modal__title {
        font-family: 'Playfair Display', serif;
        font-size: 22px;
        font-weight: 700;
        text-align: center;
        margin-bottom: 8px;
    }
    .force-password-modal__desc {
        font-size: 14px;
        color: rgba(241, 240, 238, 0.72);
        text-align: center;
        margin-bottom: 18px;
    }
    .force-password-modal__alert {
        background: rgba(255, 107, 107, 0.15);
        border: 1px solid rgba(255, 107, 107, 0.4);
        color: #ffd6d6;
        padding: 10px 12px;
        border-radius: 12px;
        font-size: 13px;
        margin-bottom: 14px;
    }
    .force-password-modal__field {
        display: flex;
        flex-direction: column;
        gap: 6px;
        margin-bottom: 12px;
    }
    .force-password-modal__field label {
        font-size: 13px;
        color: rgba(241, 240, 238, 0.85);
    }
    .force-password-modal__field input {
        width: 100%;
        padding: 12px 14px;
        border-radius: 12px;
        border: 1px solid rgba(255, 255, 255, 0.12);
        background: rgba(255, 255, 255, 0.08);
        color: #f1f0ee;
        font-size: 14px;
        outline: none;
    }
    .force-password-modal__field input:focus {
        border-color: rgba(210, 160, 120, 0.7);
        box-shadow: 0 0 0 2px rgba(210, 160, 120, 0.2);
    }
    .force-password-modal__hint {
        font-size: 12px;
        color: rgba(241, 240, 238, 0.6);
        margin-bottom: 16px;
    }
    .force-password-modal__submit {
        width: 100%;
        padding: 12px 16px;
        border-radius: 999px;
        border: none;
        background: #c49a6c;
        color: #1a120c;
        font-weight: 600;
        letter-spacing: 0.02em;
        cursor: pointer;
    }
    .force-password-modal__submit:hover {
        background: #d4aa7a;
    }
</style>
<script>
    // Sidebar toggle
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('open');
        document.getElementById('sidebar-overlay').classList.toggle('open');
    }

    // Submenu toggle
    function toggleMenu(menuId, trigger) {
        const menu = document.getElementById(menuId);
        const isOpen = menu.classList.contains('open');
        // Close all
        document.querySelectorAll('.nav-submenu').forEach(m => m.classList.remove('open'));
        document.querySelectorAll('.nav-group-toggle').forEach(t => t.classList.remove('open'));
        // Open clicked if it was closed
        if (!isOpen) {
            menu.classList.add('open');
            trigger.classList.add('open');
        }
    }

    // Modal helpers
    function openModal(id) {
        document.getElementById(id).classList.add('open');
        document.body.style.overflow = 'hidden';
    }
    function closeModal(id) {
        document.getElementById(id).classList.remove('open');
        document.body.style.overflow = '';
    }

    // Tab helpers
    function switchTab(tabId, btnEl) {
        const parent = btnEl.closest('.tab-container') || document.body;
        parent.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        parent.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
        btnEl.classList.add('active');
        document.getElementById(tabId).classList.add('active');
    }

    // Notification dropdown toggle
    function toggleNotif(event) {
        event.stopPropagation();
        const menu = document.getElementById('notif-dropdown');
        if (!menu) return;
        menu.classList.toggle('open');
    }

    // Confirm delete
    function confirmDelete(form, msg) {
        if (confirm(msg || 'Bạn có chắc muốn xóa mục này?')) {
            form.submit();
        }
    }

    function toggleProfileMenu(event) {
        event.stopPropagation();
        const menu = document.getElementById('sidebar-user-menu');
        menu.classList.toggle('open');
    }

    document.addEventListener('click', function (event) {
        const wrap = document.getElementById('sidebar-user-menu-wrap');
        const menu = document.getElementById('sidebar-user-menu');
        const notifWrap = document.getElementById('notification-wrap');
        const notifMenu = document.getElementById('notif-dropdown');

        if (!wrap || !menu) return;
        if (!wrap.contains(event.target)) {
            menu.classList.remove('open');
        }

        if (notifWrap && notifMenu && !notifWrap.contains(event.target)) {
            notifMenu.classList.remove('open');
        }
    });

    // Auto-close alerts
    document.querySelectorAll('.alert').forEach(el => {
        setTimeout(() => {
            el.style.opacity = '0';
            el.style.transition = 'opacity 0.5s';
            setTimeout(() => el.remove(), 500);
        }, 5000);
    });

    (function () {
        const forceModal = document.getElementById('force-password-modal');
        if (forceModal) {
            forceModal.classList.add('is-open');
            document.body.style.overflow = 'hidden';
        }
    })();

    // Init open submenus based on active state
    document.querySelectorAll('.nav-group-toggle.active').forEach(toggle => {
        toggle.classList.add('open');
        const menuId = toggle.getAttribute('onclick')?.match(/'([^']+)'/)?.[1];
        if (menuId) document.getElementById(menuId)?.classList.add('open');
    });
</script>

@stack('scripts')
</body>
</html>
