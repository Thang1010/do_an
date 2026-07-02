<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Quản lý') — XM Coffee</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo_web.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/logo_web.png') }}">
    <meta name="description" content="Trang quản lý hệ thống XM Coffee">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Playfair+Display:wght@400;600;700&family=Poppins:wght@400;500;600&display=swap"
        rel="stylesheet">

    <link rel="stylesheet"
        href="{{ asset('css/manager-layout.css') }}?v={{ filemtime(public_path('css/manager-layout.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/font-override.css') }}?v={{ filemtime(public_path('css/font-override.css')) }}">

    @stack('styles')
</head>

<body>

    <!-- Sidebar Overlay (mobile) -->
    <div id="sidebar-overlay" onclick="toggleSidebar()"></div>

    <!-- =============== SIDEBAR =============== -->
    <aside id="sidebar">
        <!-- Brand -->
        <div class="sidebar-brand" style="padding: 16px 20px;">
            <a href="{{ route('home') }}" class="sidebar-brand-logo"
                style="display: flex; justify-content: center; width: 100%;">
                <img src="{{ asset('images/logo.png') }}" alt="Logo"
                    style="width: 200px; height: 80px; object-fit: contain;"
                    onerror="this.parentElement.innerHTML='<span style=\'color:white; font-size:24px; font-weight:bold;\'>XM Coffee</span>'" />
            </a>
        </div>

        <!-- Navigation -->
        <nav class="sidebar-nav">
            @php
                $isStoreOwner = auth()->check() && auth()->user()->vai_tro === 'chủ cửa hàng';
                $loadInventoryPurposes = function () {
                    return \App\Models\NguyenLieu::query()
                        ->whereNotNull('muc_dich_su_dung')
                        ->where('muc_dich_su_dung', '!=', '')
                        ->orderBy('muc_dich_su_dung')
                        ->pluck('muc_dich_su_dung')
                        ->unique()
                        ->values()
                        ->toArray();
                };
                // Cache 5 phút cho nhẹ; nếu cache backend lỗi (vd Redis sập) thì
                // vẫn truy vấn trực tiếp để không làm sập cả khu quản lý.
                try {
                    $inventoryPurposes = \Illuminate\Support\Facades\Cache::remember(
                        \App\Models\NguyenLieu::PURPOSES_CACHE_KEY,
                        now()->addMinutes(5),
                        $loadInventoryPurposes
                    );
                } catch (\Throwable $e) {
                    $inventoryPurposes = $loadInventoryPurposes();
                }
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
                Mã giảm giá / Khuyến mãi
            </a>

            <!-- NHÂN SỰ -->
            <div class="nav-section-label">Nhân sự</div>

            <a href="{{ route('manager.positions.index') }}"
                class="nav-item {{ request()->routeIs('manager.positions*') ? 'active' : '' }}">
                Quản lý chức vụ
            </a>

            @php
                $canManageAdmins = $isStoreOwner;

                // Đếm số tài khoản đang chờ duyệt (theo quyền xác nhận của người đăng nhập)
                // để hiển thị badge cho biết "chỗ nào" đang có yêu cầu.
                $pendingStaffCount = 0;
                $pendingAdminCount = 0;
                if (auth()->check() && in_array(auth()->user()->vai_tro, ['chủ cửa hàng', 'quản lý'], true)) {
                    $actorStoreId = auth()->user()->cua_hang_id ?? auth()->user()->hoSoQuanLy?->cua_hang_id;
                    $pendingBase = \App\Models\NguoiDung::where('trang_thai', 'ngưng hoạt động');
                    if ($actorStoreId) {
                        $pendingBase->where(function ($q) use ($actorStoreId) {
                            $q->where('cua_hang_id', $actorStoreId)->orWhereNull('cua_hang_id');
                        });
                    }
                    $pendingStaffCount = (clone $pendingBase)->where('vai_tro', 'nhân viên')->count();
                    if (auth()->user()->vai_tro === 'chủ cửa hàng') {
                        $pendingAdminCount = (clone $pendingBase)->whereIn('vai_tro', ['quản lý', 'chủ cửa hàng'])->count();
                    }
                }
                $pendingApprovalTotal = $pendingStaffCount + $pendingAdminCount;
            @endphp

            <div class="nav-item nav-group-toggle {{ request()->routeIs('manager.users*') ? 'active open' : '' }}"
                onclick="toggleMenu('menu-users', this)">
                Quản lý người dùng
                @if($pendingApprovalTotal > 0)
                    <span class="nav-pending-badge">{{ min($pendingApprovalTotal, 99) }}</span>
                @endif
            </div>
            <div class="nav-submenu {{ request()->routeIs('manager.users*') ? 'open' : '' }}" id="menu-users">
                <a href="{{ route('manager.users.customers') }}"
                    class="nav-item {{ request()->routeIs('manager.users.customers') ? 'active' : '' }}">
                    Khách hàng
                </a>
                <a href="{{ route('manager.users.staffs') }}"
                    class="nav-item {{ request()->routeIs('manager.users.staff') || request()->routeIs('manager.users.staffs') ? 'active' : '' }}">
                    Nhân viên
                    @if($pendingStaffCount > 0)
                        <span class="nav-pending-badge">{{ min($pendingStaffCount, 99) }}</span>
                    @endif
                </a>
                @if($canManageAdmins)
                    <a href="{{ route('manager.users.admins') }}"
                        class="nav-item {{ request()->routeIs('manager.users.admins') ? 'active' : '' }}">
                        Quản lý
                        @if($pendingAdminCount > 0)
                            <span class="nav-pending-badge">{{ min($pendingAdminCount, 99) }}</span>
                        @endif
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
            <div class="nav-submenu {{ request()->routeIs('manager.ingredients*') ? 'open' : '' }}"
                id="menu-ingredients">
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
            <div class="nav-submenu {{ request()->routeIs('manager.expenses*') || request()->routeIs('manager.salary*') ? 'open' : '' }}"
                id="menu-expenses">
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
                        {{ $purposeStr }}
                    </a>
                @endforeach
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
                    Doanh thu & Đơn hàng
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
                        <div class="sidebar-user-avatar" style="padding:0; overflow:hidden; border: 2px solid #C8A97E;">
                            <img src="{{ $currentUser?->avatar_url }}" alt="Avatar"
                                style="width:100%; height:100%; object-fit:cover;">
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
                    <form method="POST" action="{{ route('auth.logout') }}" id="logout-form">
                        @csrf
                    </form>
                    <button type="button" class="sidebar-user-menu-item sidebar-user-menu-logout"
                        onclick="openLogoutModal()">Đăng xuất</button>
                </div>
            </div>
        </div>
    </aside>

    {{-- Giữ nguyên vị trí cuộn của sidebar khi chuyển trang (chạy ngay khi parse
         tới đây để khôi phục trước khi vẽ, tránh hiện tượng nhảy về đầu). --}}
    <script>
        (function () {
            var KEY = 'mgr_sidebar_scroll';
            var sidebar = document.getElementById('sidebar');
            if (!sidebar) return;

            var saved = sessionStorage.getItem(KEY);
            if (saved !== null) sidebar.scrollTop = parseInt(saved, 10) || 0;

            var t = null;
            sidebar.addEventListener('scroll', function () {
                if (t) return;
                t = setTimeout(function () {
                    sessionStorage.setItem(KEY, sidebar.scrollTop);
                    t = null;
                }, 100);
            });
            window.addEventListener('beforeunload', function () {
                sessionStorage.setItem(KEY, sidebar.scrollTop);
            });
        })();
    </script>

    <!-- =============== MAIN WRAPPER =============== -->
    <div id="main-wrapper">

        <!-- Header -->
        <header id="main-header" style="position: relative;">
            <button class="header-toggle" onclick="toggleSidebar()" aria-label="Menu">
                <span></span><span></span><span></span>
            </button>

            <div class="header-breadcrumb">
                {!! html_entity_decode($__env->yieldContent('breadcrumb', '<strong>Bảng điều khiển</strong>')) !!}
            </div>

            <!-- Title in left-center of header -->
            <div
                style="position: absolute; left: 40%; transform: translateX(-50%); top: 0; height: 100%; display: flex; align-items: center; font-family: 'Playfair Display', serif; font-size: 1.5rem; font-weight: 700; color: var(--text-dark); letter-spacing: 1px; pointer-events: none; z-index: 101;">
                XM Coffee
            </div>

            <div class="header-search" style="visibility: hidden; width: 0;">
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
                    <button class="header-btn" id="notif-btn" type="button" onclick="toggleNotif(event)"
                        aria-label="Thông báo">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M18 8a6 6 0 10-12 0c0 7-3 9-3 9h18s-3-2-3-9"></path>
                            <path d="M13.73 21a2 2 0 01-3.46 0"></path>
                        </svg>
                        @if($unreadNotificationCount > 0)
                            <span class="badge">{{ min($unreadNotificationCount, 99) }}</span>
                        @endif
                    </button>

                    <div class="notification-dropdown" id="notif-dropdown">
                        <div class="notification-dropdown-header">
                            <div style="display: flex; align-items: center; gap: 6px;">
                                <span>Thông báo gần đây</span>
                                <button type="button" onclick="reloadNotificationDropdown(event)" title="Làm mới" style="background: none; border: none; cursor: pointer; padding: 2px; display: flex; align-items: center; color: var(--text-muted); transition: color 0.2s;" onmouseenter="this.style.color='var(--text-dark)'" onmouseleave="this.style.color='var(--text-muted)'">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M23 4v6h-6"></path>
                                        <path d="M1 20v-6h6"></path>
                                        <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
                                    </svg>
                                </button>
                            </div>
                            <form method="POST" action="{{ route('manager.notifications.read-all') }}">
                                @csrf
                                <button type="submit" class="notif-mark-all">Đọc tất cả</button>
                            </form>
                        </div>

                        <div class="notification-dropdown-list">
                            @include('partials.notification-items', [
                                'recentNotifications' => $recentNotifications,
                                'openRoute' => 'manager.notifications.open',
                            ])
                        </div>

                        <div class="notification-dropdown-footer">
                            <a href="#" class="view-all-notifs-btn" onclick="loadAllNotifications(event)">Xem tất cả thông báo</a>
                        </div>
                    </div>
                </div>


            </div>
        </header>

        <!-- Alerts from session -->
        @if(session('success') || session('error') || session('warning'))
            <div style="padding: 12px 28px 0;">
                @if(session('success'))
                    <div class="alert alert-success">
                        {{ is_array(session('success')) ? implode(', ', session('success')) : session('success') }}
                    </div>
                @endif
                @if(session('error'))
                    <div class="alert alert-error">
                        {{ is_array(session('error')) ? implode(', ', session('error')) : session('error') }}
                    </div>
                @endif
                @if(session('warning'))
                    <div class="alert alert-warning">
                        {{ is_array(session('warning')) ? implode(', ', session('warning')) : session('warning') }}
                    </div>
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

        <!-- Modal thông báo không có quyền truy cập -->
        @if(session('access_denied'))
            <div class="modal-backdrop" id="access-denied-modal">
                <div class="modal-box" style="max-width: 420px; width: calc(100% - 32px);">
                    <div class="modal-header">
                        <span class="modal-title">Không có quyền truy cập</span>
                        <button type="button" class="modal-close" onclick="closeModal('access-denied-modal')">&times;</button>
                    </div>
                    <div class="modal-body" style="text-align:center;">
                        <div style="font-size:42px; line-height:1; margin-bottom:12px;">🔒</div>
                        <p style="margin:0;">{{ session('access_denied') }}</p>
                    </div>
                    <div class="modal-footer" style="justify-content:center;">
                        <button type="button" class="btn btn-primary" onclick="closeModal('access-denied-modal')">Đã hiểu</button>
                    </div>
                </div>
            </div>
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    if (typeof openModal === 'function') {
                        openModal('access-denied-modal');
                    }
                });
            </script>
        @endif

        @if(session('force_password_setup') && auth()->check())
            <div id="force-password-modal" class="force-password-modal" role="dialog" aria-modal="true"
                aria-label="Đặt mật khẩu XM COFFEE">
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
                            <input id="force-password" type="password" name="password" autocomplete="new-password"
                                placeholder="Nhập mật khẩu mới" required>
                        </div>
                        <div class="force-password-modal__field">
                            <label for="force-password-confirm">Nhập lại mật khẩu</label>
                            <input id="force-password-confirm" type="password" name="password_confirmation"
                                autocomplete="new-password" placeholder="Nhập lại mật khẩu" required>
                        </div>
                        <div class="force-password-modal__hint">Mật khẩu tối thiểu 8 ký tự.</div>
                        <button type="submit" class="force-password-modal__submit">Lưu mật khẩu</button>
                    </form>
                </div>
            </div>
        @endif

        @if(session('needs_start_cash_for_shift'))
            <div id="start-cash-modal" class="force-password-modal is-open" role="dialog" aria-modal="true">
                <div class="force-password-modal__backdrop"></div>
                <div class="force-password-modal__panel">
                    <div class="force-password-modal__title">Nhập số tiền đầu ca</div>
                    <p class="force-password-modal__desc">Vui lòng điền số tiền đầu ca trước khi thực hiện giao dịch.</p>
                    <form method="POST"
                        action="{{ auth()->user()->vai_tro === 'nhân viên' ? route('staff.shifts.start-cash') : route('manager.shift-close.start') }}">
                        @csrf
                        <input type="hidden" name="ca_lam_viec_id" value="{{ session('needs_start_cash_for_shift') }}">
                        <div class="force-password-modal__field">
                            <label>Số tiền đầu ca (VNĐ)</label>
                            <input type="text" class="format-money" name="so_tien_dau_ca" required
                                placeholder="Ví dụ: 500,000">
                        </div>
                        <button type="submit" class="force-password-modal__submit">Xác nhận</button>
                        <button type="button" class="force-password-modal__submit"
                            style="background: transparent; border: 1px solid #c49a6c; margin-top: 10px; color: #c49a6c;"
                            onclick="document.getElementById('start-cash-modal').style.display='none'">Hủy</button>
                    </form>
                </div>
            </div>
        @endif

        <!-- Page content -->
        <main id="main-content">
            @yield('content')
        </main>

    </div>

    <!-- =============== GLOBAL DELETE CONFIRM MODAL =============== -->
    <div id="delete-confirm-modal" class="force-password-modal" style="display:none;" role="dialog" aria-modal="true"
        aria-labelledby="delete-confirm-title">
        <div id="delete-confirm-backdrop" class="force-password-modal__backdrop"></div>
        <div class="force-password-modal__panel" style="width: min(420px, 92vw);">
            <div class="force-password-modal__title" id="delete-confirm-title"
                style="display: flex; align-items: center; justify-content: center; gap: 10px;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#ff6b6b" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="3 6 5 6 21 6" />
                    <path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6" />
                    <path d="M10 11v6" />
                    <path d="M14 11v6" />
                    <path d="M9 6V4h6v2" />
                </svg>
                Xác nhận xóa
            </div>
            <p id="delete-confirm-message" class="force-password-modal__desc"
                style="margin-bottom: 25px; margin-top: 10px; padding: 0 10px;">Bạn có chắc chắn muốn xóa mục này không?
                Hành động này không thể hoàn tác.</p>
            <div style="display: flex; gap: 12px; justify-content: center;">
                <button id="delete-confirm-cancel" type="button" class="force-password-modal__submit"
                    style="background: transparent; border: 1px solid rgba(241, 240, 238, 0.4); color: rgba(241, 240, 238, 0.8);">Hủy</button>
                <button id="delete-confirm-ok" type="button" class="force-password-modal__submit"
                    style="background: #d92d20; color: #fff;">Xác nhận xóa</button>
            </div>
        </div>
    </div>

    <!-- =============== LOGOUT CONFIRM MODAL =============== -->
    <div id="logout-confirm-modal" class="force-password-modal" style="display:none;" role="dialog" aria-modal="true">
        <div id="logout-confirm-backdrop" class="force-password-modal__backdrop"></div>
        <div class="force-password-modal__panel" style="width: min(400px, 92vw);">
            <div class="force-password-modal__title" style="display: flex; align-items: center; justify-content: center; gap: 10px;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#c49a6c" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                    <polyline points="16 17 21 12 16 7"/>
                    <line x1="21" y1="12" x2="9" y2="12"/>
                </svg>
                Xác nhận đăng xuất
            </div>
            <p class="force-password-modal__desc" style="margin-top: 10px; margin-bottom: 28px; padding: 0 10px;">
                Bạn có chắc chắn muốn đăng xuất khỏi hệ thống không?
            </p>
            <div style="display: flex; gap: 12px; justify-content: center;">
                <button id="logout-confirm-cancel" type="button" class="force-password-modal__submit"
                    style="background: transparent; border: 1px solid rgba(241, 240, 238, 0.4); color: rgba(241, 240, 238, 0.8);">
                    Hủy
                </button>
                <button id="logout-confirm-ok" type="button" class="force-password-modal__submit"
                    style="background: #c49a6c; color: #1a120c;">
                    Đăng xuất
                </button>
            </div>
        </div>
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
            background: rgba(30, 17, 6, 0.92);
            border: 1px solid rgba(240, 221, 184, 0.16);
            backdrop-filter: blur(14px);
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

        window.isShowingAllNotifications = false;
        function loadAllNotifications(event) {
            event.preventDefault();
            var list = document.querySelector('#notif-dropdown .notification-dropdown-list');
            if (!list) return;

            list.innerHTML = '<div class="notification-empty">Đang tải tất cả thông báo...</div>';

            var url = '{{ route('manager.notifications.poll') }}?all=1';
            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
                .then(function (r) { return r.ok ? r.json() : null; })
                .then(function (data) {
                    if (!data) return;
                    var btn = document.getElementById('notif-btn');
                    if (btn) {
                        var badge = btn.querySelector('.badge');
                        if (data.count > 0) {
                            if (!badge) {
                                badge = document.createElement('span');
                                badge.className = 'badge';
                                btn.appendChild(badge);
                            }
                            badge.textContent = data.count > 99 ? 99 : data.count;
                        } else if (badge) {
                            badge.remove();
                        }
                    }
                    if (typeof data.html === 'string') {
                        list.innerHTML = data.html;
                    }
                    window.isShowingAllNotifications = true;
                })
                .catch(function () {
                    list.innerHTML = '<div class="notification-empty">Lỗi khi tải thông báo.</div>';
                });
        }

        function reloadNotificationDropdown(event) {
            if (event) event.preventDefault();
            var list = document.querySelector('#notif-dropdown .notification-dropdown-list');
            if (!list) return;

            var btn = event ? event.currentTarget : null;
            if (btn) {
                var svg = btn.querySelector('svg');
                if (svg) {
                    svg.style.transition = 'transform 0.5s ease-in-out';
                    svg.style.transform = 'rotate(360deg)';
                    setTimeout(function() {
                        svg.style.transition = 'none';
                        svg.style.transform = 'none';
                    }, 500);
                }
            }

            var showAll = window.isShowingAllNotifications;
            var url = '{{ route('manager.notifications.poll') }}' + (showAll ? '?all=1' : '');

            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
                .then(function (r) { return r.ok ? r.json() : null; })
                .then(function (data) {
                    if (!data) return;
                    var notifBtn = document.getElementById('notif-btn');
                    if (notifBtn) {
                        var badge = notifBtn.querySelector('.badge');
                        if (data.count > 0) {
                            if (!badge) {
                                badge = document.createElement('span');
                                badge.className = 'badge';
                                notifBtn.appendChild(badge);
                            }
                            badge.textContent = data.count > 99 ? 99 : data.count;
                        } else if (badge) {
                            badge.remove();
                        }
                    }
                    if (typeof data.html === 'string') {
                        list.innerHTML = data.html;
                    }
                })
                .catch(function () {
                    // silently fail
                });
        }

        // ── Chuông báo thông báo mới (WebAudio — không cần file âm thanh) ──
        var __notifAudioCtx = null;
        function __initNotifAudio() {
            if (!__notifAudioCtx) {
                try { __notifAudioCtx = new (window.AudioContext || window.webkitAudioContext)(); }
                catch (e) { __notifAudioCtx = null; }
            }
            if (__notifAudioCtx && __notifAudioCtx.state === 'suspended') __notifAudioCtx.resume();
        }
        // Trình duyệt chặn phát âm thanh cho tới khi người dùng tương tác với trang lần đầu.
        document.addEventListener('click', __initNotifAudio, { once: true });
        document.addEventListener('keydown', __initNotifAudio, { once: true });

        function playNotifBell() {
            if (!__notifAudioCtx) return;
            var ctx = __notifAudioCtx;
            var now = ctx.currentTime;
            // Chuỗi chuông TO & DÀI hơn: lặp "ting-ting" 3 lần trong ~1.8 giây.
            var notes = [
                [0.00, 880], [0.20, 1174.66],
                [0.60, 880], [0.80, 1174.66],
                [1.20, 880], [1.40, 1174.66]
            ];
            notes.forEach(function (pair) {
                var t = now + pair[0];
                var osc = ctx.createOscillator();
                var gain = ctx.createGain();
                osc.type = 'triangle'; // đầy tiếng hơn sine → nghe to/rõ hơn
                osc.frequency.value = pair[1];
                gain.gain.setValueAtTime(0.0001, t);
                gain.gain.exponentialRampToValueAtTime(0.8, t + 0.02); // to hơn (0.35 → 0.8)
                gain.gain.exponentialRampToValueAtTime(0.0001, t + 0.40);
                osc.connect(gain).connect(ctx.destination);
                osc.start(t);
                osc.stop(t + 0.44);
            });
        }

        // ── Polling thông báo: tự cập nhật badge + danh sách, không cần F5 ──
        (function () {
            var POLL_URL = '{{ route('manager.notifications.poll') }}';
            var INTERVAL = 10000; // 10 giây
            var inFlight = false;
            var lastCount = {{ (int) $unreadNotificationCount }}; // số chưa đọc lúc tải trang

            function updateBadge(count) {
                var btn = document.getElementById('notif-btn');
                if (!btn) return;
                var badge = btn.querySelector('.badge');
                if (count > 0) {
                    if (!badge) {
                        badge = document.createElement('span');
                        badge.className = 'badge';
                        btn.appendChild(badge);
                    }
                    badge.textContent = count > 99 ? 99 : count;
                } else if (badge) {
                    badge.remove();
                }
            }

            function poll() {
                if (inFlight || document.hidden) return;
                // Không cập nhật khi dropdown đang mở để tránh giật nội dung đang đọc
                var dropdown = document.getElementById('notif-dropdown');
                if (dropdown && dropdown.classList.contains('open')) return;

                inFlight = true;
                fetch(POLL_URL, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
                    .then(function (r) { return r.ok ? r.json() : null; })
                    .then(function (data) {
                        if (!data) return;
                        var count = data.count || 0;
                        // Có thông báo MỚI (số chưa đọc tăng) → kêu chuông.
                        if (count > lastCount) playNotifBell();
                        lastCount = count;
                        updateBadge(count);
                        var list = document.querySelector('#notif-dropdown .notification-dropdown-list');
                        if (list && typeof data.html === 'string') list.innerHTML = data.html;
                    })
                    .catch(function () { /* im lặng, tránh chặn UI */ })
                    .finally(function () { inFlight = false; });
            }

            setInterval(poll, INTERVAL);
        })();

        // Confirm delete — shows a centered modal instead of browser confirm()
        (function () {
            var modal = document.getElementById('delete-confirm-modal');
            var msgEl = document.getElementById('delete-confirm-message');
            var okBtn = document.getElementById('delete-confirm-ok');
            var cancelBtn = document.getElementById('delete-confirm-cancel');
            var backdrop = document.getElementById('delete-confirm-backdrop');
            var pendingForm = null;

            function openDeleteModal(form, msg) {
                pendingForm = form;
                if (msgEl) msgEl.textContent = msg || 'Bạn có chắc chắn muốn xóa mục này không? Hành động này không thể hoàn tác.';
                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
            }

            function closeDeleteModal() {
                modal.style.display = 'none';
                document.body.style.overflow = '';
                pendingForm = null;
            }

            if (okBtn) okBtn.addEventListener('click', function () {
                if (pendingForm) { pendingForm.submit(); }
                closeDeleteModal();
            });
            if (cancelBtn) cancelBtn.addEventListener('click', closeDeleteModal);
            if (backdrop) backdrop.addEventListener('click', closeDeleteModal);

            window.confirmDelete = function (form, msg) {
                openDeleteModal(form, msg);
                return false;
            };
        })();

        function toggleProfileMenu(event) {
            event.stopPropagation();
            const menu = document.getElementById('sidebar-user-menu');
            menu.classList.toggle('open');
        }

        // Logout confirm modal
        (function () {
            var modal = document.getElementById('logout-confirm-modal');
            var backdrop = document.getElementById('logout-confirm-backdrop');
            var okBtn = document.getElementById('logout-confirm-ok');
            var cancelBtn = document.getElementById('logout-confirm-cancel');

            window.openLogoutModal = function () {
                document.getElementById('sidebar-user-menu').classList.remove('open');
                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
            };

            function closeLogoutModal() {
                modal.style.display = 'none';
                document.body.style.overflow = '';
            }

            if (okBtn) okBtn.addEventListener('click', function () {
                document.getElementById('logout-form').submit();
            });
            if (cancelBtn) cancelBtn.addEventListener('click', closeLogoutModal);
            if (backdrop) backdrop.addEventListener('click', closeLogoutModal);
        })();

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
                window.isShowingAllNotifications = false;
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

        // Auto format money globally
        document.addEventListener('DOMContentLoaded', function () {
            const formatMoney = (val) => {
                let number = String(val).replace(/[^\d]/g, '');
                if (!number) return '';
                return Number(number).toLocaleString('en-US');
            };

            const initMoneyInputs = () => {
                document.querySelectorAll('.format-money').forEach(input => {
                    if (input.type === 'number') input.type = 'text'; // Convert to text to allow commas
                    input.addEventListener('input', function (e) {
                        let cursorPosition = this.selectionStart;
                        let originalLength = this.value.length;
                        this.value = formatMoney(this.value);
                        let newLength = this.value.length;
                        if (this === document.activeElement) {
                            this.setSelectionRange(cursorPosition + (newLength - originalLength), cursorPosition + (newLength - originalLength));
                        }
                    });
                    if (input.value) {
                        input.value = formatMoney(input.value);
                    }
                });
            };

            initMoneyInputs();

            // Strip commas on form submit
            document.querySelectorAll('form').forEach(form => {
                form.addEventListener('submit', function (e) {
                    var invalidInputs = this.querySelectorAll('[data-invalid="true"]');
                    if (invalidInputs.length > 0) {
                        e.preventDefault();
                        invalidInputs[0].focus();
                        var originalBg = invalidInputs[0].style.backgroundColor;
                        invalidInputs[0].style.backgroundColor = '#fecaca';
                        setTimeout(() => { invalidInputs[0].style.backgroundColor = originalBg; }, 300);
                        return false;
                    }
                    this.querySelectorAll('.format-money').forEach(input => {
                        input.value = input.value.replace(/,/g, '');
                    });
                });
            });

            // Global Inline Validation cho mọi form thêm, sửa
            document.addEventListener('input', function(e) {
                var input = e.target;
                if (input.tagName !== 'INPUT' && input.tagName !== 'TEXTAREA') return;
                if (['hidden', 'checkbox', 'radio', 'submit', 'button', 'file'].includes(input.type)) return;

                var rawValue = input.value;
                var isValid = true;
                var errorMsg = '';

                if (rawValue !== '') {
                    // Nhận diện loại trường
                    var isMoney = input.classList.contains('format-money');
                    var isNumberType = input.type === 'number';
                    var isNumericName = ['gia_', 'so_luong', 'chi_phi', 'chiet_khau', 'he_so_gia', 'so_ban', 'so_tien'].some(n => input.name && input.name.includes(n));
                    
                    var isPhoneField = input.type === 'tel' || (input.name && input.name.includes('dien_thoai'));
                    var isPersonName = input.name === 'ho_ten' || input.name === 'ten_nhan_vien' || input.name === 'ten_khach_hang';
                    var isEmailField = input.type === 'email' || input.name === 'email';

                    if (isPhoneField) {
                        var hasLettersPhone = /[^0-9\s\+\-\(\)]/.test(rawValue);
                        if (hasLettersPhone) {
                            isValid = false;
                            errorMsg = 'Số điện thoại chỉ được chứa các chữ số!';
                        }
                    } else if (isPersonName) {
                        var hasNumbersOrSpecial = /[0-9!@#$%^&*()_+={}\[\]:;"'<>,.?\\|]/g.test(rawValue);
                        if (hasNumbersOrSpecial) {
                            isValid = false;
                            errorMsg = 'Tên người không được chứa số hay ký tự đặc biệt!';
                        }
                    } else if (isEmailField) {
                        var hasSpacesOrAccents = /[ \u00C0-\u024F\u1E00-\u1EFF]/.test(rawValue);
                        if (hasSpacesOrAccents) {
                            isValid = false;
                            errorMsg = 'Email không được chứa khoảng trắng hoặc có dấu Tiếng Việt!';
                        }
                    } else if (isNumberType || isMoney || isNumericName) {
                        var hasLetters = /[^0-9.,]/.test(rawValue);
                        if (hasLetters) {
                            isValid = false;
                            errorMsg = 'Trường này chỉ được nhập số, không nhập chữ cái!';
                        }
                    }
                }

                if (!isValid) {
                    input.style.borderColor = '#d92d20';
                    input.style.backgroundColor = '#fef2f2';
                    input.dataset.invalid = "true";
                    
                    var errEl = input.nextElementSibling;
                    if (!errEl || !errEl.classList.contains('global-inline-error')) {
                        errEl = document.createElement('div');
                        errEl.className = 'global-inline-error form-error';
                        errEl.style.fontSize = '12px';
                        errEl.style.color = '#d92d20';
                        errEl.style.marginTop = '4px';
                        errEl.style.fontWeight = '500';
                        input.parentNode.insertBefore(errEl, input.nextSibling);
                    }
                    errEl.textContent = errorMsg;
                    errEl.style.display = 'block';
                } else {
                    input.style.borderColor = '';
                    input.style.backgroundColor = '';
                    delete input.dataset.invalid;
                    var errEl = input.nextElementSibling;
                    if (errEl && errEl.classList.contains('global-inline-error')) {
                        errEl.style.display = 'none';
                    }
                }
            }, true); // use capture phase so we can catch it before format-money modifies it!
        });
    </script>

    @include('partials.app-modals')
    @include('partials.flatpickr')
    @stack('scripts')
</body>

</html>