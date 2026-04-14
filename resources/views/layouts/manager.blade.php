<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Quản lý') — XM Coffee</title>
    <meta name="description" content="Trang quản lý hệ thống XM Coffee">
    <script src="https://cdn.tailwindcss.com"></script>
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

        <a href="{{ route('manager.vouchers.index') }}"
           class="nav-item {{ request()->routeIs('manager.vouchers*') ? 'active' : '' }}">
            Voucher / Khuyến mãi
        </a>

        <!-- NHÂN SỰ -->
        <div class="nav-section-label">Nhân sự</div>

        <div class="nav-item nav-group-toggle {{ request()->routeIs('manager.users*') ? 'active open' : '' }}"
             onclick="toggleMenu('menu-users', this)">
            Quản lý người dùng
        </div>
        <div class="nav-submenu {{ request()->routeIs('manager.users*') ? 'open' : '' }}" id="menu-users">
            <a href="{{ route('manager.users.customers') }}"
               class="nav-item {{ request()->routeIs('manager.users.customers') ? 'active' : '' }}">
                Khách hàng
            </a>
                <a href="{{ route('manager.users.staffs') }}"
                    class="nav-item {{ request()->routeIs('manager.users.staff') || request()->routeIs('manager.users.staffs') ? 'active' : '' }}">
                Nhân viên
            </a>
                <a href="{{ route('manager.users.admins') }}"
                    class="nav-item {{ request()->routeIs('manager.users.admins') ? 'active' : '' }}">
                     Quản lý
                </a>
        </div>

        <div class="nav-item nav-group-toggle {{ request()->routeIs('manager.shifts*') || request()->routeIs('manager.payroll*') ? 'active open' : '' }}"
             onclick="toggleMenu('menu-staff-mgmt', this)">
            Quản lý nhân viên
        </div>
        <div class="nav-submenu {{ request()->routeIs('manager.shifts*') || request()->routeIs('manager.payroll*') ? 'open' : '' }}" id="menu-staff-mgmt">
            <a href="{{ route('manager.shifts.index') }}"
               class="nav-item {{ request()->routeIs('manager.shifts*') ? 'active' : '' }}">
                Ca làm việc
            </a>
            <a href="{{ route('manager.shifts.attendance') }}"
               class="nav-item">
                Chấm công
            </a>
            <a href="{{ route('manager.payroll.index') }}"
               class="nav-item {{ request()->routeIs('manager.payroll*') ? 'active' : '' }}">
                Bảng lương
            </a>
        </div>

        <!-- KHO / TÀI CHÍNH -->
        <div class="nav-section-label">Kho & Tài chính</div>

        <div class="nav-item nav-group-toggle {{ request()->routeIs('manager.inventory*') ? 'active open' : '' }}"
             onclick="toggleMenu('menu-inventory', this)">
            Quản lý kho
        </div>
        <div class="nav-submenu {{ request()->routeIs('manager.inventory*') ? 'open' : '' }}" id="menu-inventory">
            <a href="{{ route('manager.inventory.index') }}"
               class="nav-item {{ request()->routeIs('manager.inventory.index') ? 'active' : '' }}">
                Tồn kho
            </a>
            <a href="{{ route('manager.inventory.import') }}"
               class="nav-item">
                Nhập kho
            </a>
            <a href="{{ route('manager.inventory.export') }}"
               class="nav-item">
                Xuất kho
            </a>
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
            <a href="{{ route('manager.reports.points') }}"
               class="nav-item">
                Điểm thưởng
            </a>
        </div>

    </nav>

    <!-- Sidebar Footer — User Info -->
    <div class="sidebar-footer">
        <div class="sidebar-user-menu-wrap" id="sidebar-user-menu-wrap">
            <button type="button" class="sidebar-user-trigger" onclick="toggleProfileMenu(event)">
                <div class="sidebar-user">
                    <div class="sidebar-user-avatar">
                        @auth
                            {{ mb_substr(auth()->user()->ho_ten ?? 'Q', 0, 1) }}
                        @else
                            Q
                        @endauth
                    </div>
                    <div class="sidebar-user-info">
                        <div class="sidebar-user-name">
                            {{ auth()->user()->ho_ten ?? 'Quản lý' }}
                        </div>
                        <div class="sidebar-user-role">{{ auth()->user()->vai_tro ?? 'quản lý' }}</div>
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
                >
                    Thông báo
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
                                $title = $data['title'] ?? 'Thông báo hệ thống';
                                $message = $data['message'] ?? 'Bạn có thông báo mới.';
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
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-error">{{ session('error') }}</div>
        @endif
        @if(session('warning'))
            <div class="alert alert-warning">{{ session('warning') }}</div>
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

    <!-- Page content -->
    <main id="main-content">
        @yield('content')
    </main>

</div>

<!-- =============== SCRIPTS =============== -->
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
