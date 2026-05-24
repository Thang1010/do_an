<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Nhân viên') — XM Coffee</title>
    <meta name="description" content="Trang nhân viên hệ thống XM Coffee">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Playfair+Display:wght@400;600;700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/manager-layout.css') }}">
    <link rel="stylesheet" href="{{ asset('css/staff-layout.css') }}">
    @stack('styles')
</head>
<body>

<!-- Sidebar Overlay (mobile) -->
<div id="sidebar-overlay" onclick="toggleSidebar()"></div>

<!-- =============== SIDEBAR =============== -->
<aside id="sidebar">
    <!-- Brand -->
    <div class="sidebar-brand">
        <a href="/staff/tables" class="sidebar-brand-logo">
            <div class="sidebar-brand-img">
                <img src="{{ asset('images/logo.png') }}" alt="Logo"
                     onerror="this.parentElement.innerHTML='XM'"/>
            </div>
            <div>
                <div class="sidebar-brand-text">Café</div>
                <div class="sidebar-brand-sub">Nhân viên POS</div>
            </div>
        </a>
    </div>

    <!-- Navigation -->
    <nav class="sidebar-nav">
        <div class="nav-section-label">Quản lý</div>

        <a href="/staff/tables"
           class="nav-item {{ request()->routeIs('staff.tables*') ? 'active' : '' }}">
            Bàn
        </a>

        <a href="/staff/orders"
           class="nav-item {{ request()->routeIs('staff.orders*') ? 'active' : '' }}">
            Lịch sử đơn hàng
        </a>

        <a href="/staff/customers"
           class="nav-item {{ request()->routeIs('staff.customers*') ? 'active' : '' }}">
            Khách hàng
        </a>

        <a href="/staff/shifts"
           class="nav-item {{ request()->routeIs('staff.shifts*') ? 'active' : '' }}">
            Ca làm việc
        </a>

        <a href="/staff/expenses"
           class="nav-item {{ request()->routeIs('staff.expenses*') ? 'active' : '' }}">
            Chi tiêu
        </a>

    </nav>

    <!-- Sidebar Footer — User Info -->
    <div class="sidebar-footer">
        <div class="sidebar-user-menu-wrap" id="sidebar-user-menu-wrap">
            <button type="button" class="sidebar-user-trigger" onclick="toggleProfileMenu(event)">
                <div class="sidebar-user">
                    <div class="sidebar-user-avatar">
                        @auth
                            {{ mb_substr(auth()->user()->ho_ten ?? 'N', 0, 1) }}
                        @else
                            N
                        @endauth
                    </div>
                    <div class="sidebar-user-info">
                        <div class="sidebar-user-name">
                            {{ auth()->user()->ho_ten ?? 'Nhân viên' }}
                        </div>
                        <div class="sidebar-user-role">{{ auth()->user()->vai_tro ?? 'nhân viên' }}</div>
                    </div>
                </div>
            </button>

            <div class="sidebar-user-menu" id="sidebar-user-menu">
                <a href="{{ route('staff.profile.edit') }}" class="sidebar-user-menu-item">Hồ sơ cá nhân</a>
                <a href="{{ route('staff.profile.edit') }}#password" class="sidebar-user-menu-item">Đổi mật khẩu</a>
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
            @yield('breadcrumb', '<strong>Bàn</strong>')
        </div>

        <div class="header-right">
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
                        <form method="POST" action="{{ route('staff.notifications.read-all') }}">
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
                            <a href="{{ route('staff.notifications.open', $notification->id) }}"
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
                        <a href="{{ route('staff.notifications.index') }}">Xem tất cả thông báo</a>
                    </div>
                </div>
            </div>
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
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('open');
        document.getElementById('sidebar-overlay').classList.toggle('open');
    }

    function openModal(id) {
        document.getElementById(id).classList.add('open');
        document.body.style.overflow = 'hidden';
    }
    function closeModal(id) {
        document.getElementById(id).classList.remove('open');
        document.body.style.overflow = '';
    }

    function toggleProfileMenu(event) {
        event.stopPropagation();
        document.getElementById('sidebar-user-menu').classList.toggle('open');
    }

    function toggleNotif(event) {
        event.stopPropagation();
        var menu = document.getElementById('notif-dropdown');
        if (!menu) return;
        menu.classList.toggle('open');
    }

    document.addEventListener('click', function (event) {
        var wrap = document.getElementById('sidebar-user-menu-wrap');
        var menu = document.getElementById('sidebar-user-menu');
        var notifWrap = document.getElementById('notification-wrap');
        var notifMenu = document.getElementById('notif-dropdown');
        if (wrap && menu && !wrap.contains(event.target)) {
            menu.classList.remove('open');
        }
        if (notifWrap && notifMenu && !notifWrap.contains(event.target)) {
            notifMenu.classList.remove('open');
        }
    });

    // Auto-close alerts
    document.querySelectorAll('.alert').forEach(function(el) {
        setTimeout(function() {
            el.style.opacity = '0';
            el.style.transition = 'opacity 0.5s';
            setTimeout(function() { el.remove(); }, 500);
        }, 5000);
    });

    (function () {
        var forceModal = document.getElementById('force-password-modal');
        if (forceModal) {
            forceModal.classList.add('is-open');
            document.body.style.overflow = 'hidden';
        }
    })();

    // Live clock
    function updateClock() {
        var el = document.getElementById('staff-live-clock');
        if (!el) return;
        var now = new Date();
        var options = { weekday: 'long', day: '2-digit', month: '2-digit', year: 'numeric' };
        var date = now.toLocaleDateString('vi-VN', options);
        var time = now.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        el.textContent = date + ' | ' + time;
    }
    setInterval(updateClock, 1000);
    document.addEventListener('DOMContentLoaded', updateClock);
</script>

@stack('scripts')
</body>
</html>
