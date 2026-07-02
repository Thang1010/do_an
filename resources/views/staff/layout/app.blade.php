<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Nhân viên') — XM Coffee</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo_web.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/logo_web.png') }}">
    <meta name="description" content="Trang nhân viên hệ thống XM Coffee">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Playfair+Display:wght@500;600;700&family=Roboto:wght@300;400;500;700&display=swap"
        rel="stylesheet">
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
        <div class="sidebar-brand" style="justify-content: center; padding: 20px 0;">
            <a href="/staff/tables" class="sidebar-brand-logo" style="justify-content: center; width: 100%;">
                <div class="sidebar-brand-img" style="width: 250px; height: 50px; margin: 0 auto;">
                    <img src="{{ asset('images/logo.png') }}" alt="Logo" onerror="this.parentElement.innerHTML='XM'"
                        style="width: 200px; height: 80px; object-fit: contain;" />
                </div>
            </a>
        </div>

        <!-- Navigation -->
        <nav class="sidebar-nav">
            <div class="nav-section-label">Quản lý</div>

            @php
                $banRungCount = auth()->check()
                    ? \App\Models\DonHang::banRung()->distinct('ban_an_id')->count('ban_an_id')
                    : 0;
            @endphp
            <a href="/staff/tables" id="tables-nav-item" class="nav-item {{ request()->routeIs('staff.tables*') ? 'active' : '' }}">
                Bàn
                <span id="ban-rung-badge" class="badge" style="background:#ea580c;color:#fff;border-radius:999px;padding:1px 8px;font-size:12px;font-weight:700;margin-left:6px;{{ $banRungCount > 0 ? '' : 'display:none;' }}">{{ min($banRungCount, 99) }}</span>
            </a>

            @php
                $takeawayQueueCount = auth()->check()
                    ? \App\Models\DonHang::takeawayQueue()->count()
                    : 0;
            @endphp
            <a href="/staff/takeaway" id="takeaway-nav-item" class="nav-item {{ request()->routeIs('staff.takeaway*') ? 'active' : '' }}">
                Đơn mang về
                <span id="takeaway-badge" class="badge" style="background:#ea580c;color:#fff;border-radius:999px;padding:1px 8px;font-size:12px;font-weight:700;margin-left:6px;{{ $takeawayQueueCount > 0 ? '' : 'display:none;' }}">{{ min($takeawayQueueCount, 99) }}</span>
            </a>

            <a href="/staff/orders" class="nav-item {{ request()->routeIs('staff.orders*') ? 'active' : '' }}">
                Lịch sử đơn hàng
            </a>

            <a href="/staff/shifts" class="nav-item {{ request()->routeIs('staff.shifts*') ? 'active' : '' }}">
                Ca làm việc
            </a>

            <a href="/staff/expenses" class="nav-item {{ request()->routeIs('staff.expenses*') ? 'active' : '' }}">
                Chi tiêu
            </a>

        </nav>

        <!-- Sidebar Footer — User Info -->
        <div class="sidebar-footer">
            <div class="sidebar-user-menu-wrap" id="sidebar-user-menu-wrap">
                <button type="button" class="sidebar-user-trigger" onclick="toggleProfileMenu(event)">
                    <div class="sidebar-user">
                        <div class="sidebar-user-avatar" style="padding:0; overflow:hidden; border: 2px solid #C8A97E;">
                            @auth
                                <img src="{{ auth()->user()->avatar_url }}" alt="Avatar" style="width:100%; height:100%; object-fit:cover;">
                            @else
                                <img src="https://ui-avatars.com/api/?name=N&background=E2D9C8&color=30261C&bold=true" alt="Avatar" style="width:100%; height:100%; object-fit:cover;">
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
                    <form method="POST" action="{{ route('auth.logout') }}" id="logout-form">@csrf</form>
                    <button type="button" class="sidebar-user-menu-item sidebar-user-menu-logout"
                        onclick="openLogoutModal()">Đăng xuất</button>
                </div>
            </div>
        </div>
    </aside>

    {{-- Giữ nguyên vị trí cuộn của sidebar khi chuyển trang. --}}
    <script>
        (function () {
            var KEY = 'staff_sidebar_scroll';
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
        <header id="main-header">
            <button class="header-toggle" onclick="toggleSidebar()" aria-label="Menu">
                <span></span><span></span><span></span>
            </button>

            <div class="header-breadcrumb"
                style="flex: 1; display: flex; justify-content: center; font-family: 'Playfair Display', serif; font-size: 26px; font-weight: 700; color: #1a120c; letter-spacing: 0.02em; text-transform: uppercase;">
                Nhân viên
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
                            <form method="POST" action="{{ route('staff.notifications.read-all') }}">
                                @csrf
                                <button type="submit" class="notif-mark-all">Đọc tất cả</button>
                            </form>
                        </div>

                        <div class="notification-dropdown-list">
                            @include('partials.notification-items', [
                                'recentNotifications' => $recentNotifications,
                                'openRoute' => 'staff.notifications.open',
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
    <div id="delete-confirm-modal"
        style="position:fixed;inset:0;display:none;align-items:center;justify-content:center;z-index:10000;padding:20px;"
        role="dialog" aria-modal="true" aria-labelledby="delete-confirm-title">
        <div id="delete-confirm-backdrop"
            style="position:absolute;inset:0;background:rgba(18,12,8,0.72);backdrop-filter:blur(2px);"></div>
        <div
            style="position:relative;width:min(420px,92vw);background:rgba(30,17,6,0.92);border-radius:18px;border:1px solid rgba(240,221,184,0.16);backdrop-filter:blur(14px);padding:28px 26px 22px;box-shadow:0 24px 60px rgba(0,0,0,0.45);font-family:'Outfit',sans-serif;">
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:10px;">
                <div
                    style="width:40px;height:40px;border-radius:50%;background:rgba(217, 45, 32, 0.15);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#d92d20" stroke-width="2.2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="3 6 5 6 21 6" />
                        <path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6" />
                        <path d="M10 11v6" />
                        <path d="M14 11v6" />
                        <path d="M9 6V4h6v2" />
                    </svg>
                </div>
                <div id="delete-confirm-title" style="font-size:17px;font-weight:700;color:#F0DDB8;">Xác nhận xóa</div>
            </div>
            <div id="delete-confirm-message"
                style="font-size:14px;color:rgba(255,255,255,0.78);margin-bottom:22px;line-height:1.55;padding-left:52px;">
                Bạn có chắc
                chắn muốn xóa mục này không? Hành động này không thể hoàn tác.</div>
            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <button id="delete-confirm-cancel" type="button"
                    style="padding:9px 20px;border-radius:8px;border:1px solid rgba(240,221,184,0.3);background:rgba(255,255,255,0.05);color:#F0DDB8;font-size:14px;font-weight:600;cursor:pointer;font-family:'Outfit',sans-serif;">Hủy</button>
                <button id="delete-confirm-ok" type="button"
                    style="padding:9px 20px;border-radius:8px;border:none;background:#d92d20;color:#fff;font-size:14px;font-weight:600;cursor:pointer;font-family:'Outfit',sans-serif;">Xác
                    nhận xóa</button>
            </div>
        </div>
    </div>

    <!-- =============== GLOBAL SIZE MODAL =============== -->
    <div id="global-size-modal"
        style="position: fixed; inset: 0; display: none; align-items: center; justify-content: center; z-index: 10001; padding: 20px;">
        <div style="position: absolute; inset: 0; background: rgba(18, 12, 8, 0.72); backdrop-filter: blur(2px);"
            onclick="closeGlobalSizeModal()"></div>
        <div
            style="position: relative; width: min(460px, 92vw); background: rgba(30, 17, 6, 0.92); border: 1px solid rgba(240, 221, 184, 0.16); border-radius: 18px; padding: 28px 26px; box-shadow: 0 24px 60px rgba(0, 0, 0, 0.45); backdrop-filter: blur(14px); color: #f1f0ee; font-family: 'Outfit', sans-serif;">
            <button type="button" onclick="closeGlobalSizeModal()"
                style="position: absolute; right: 16px; top: 12px; background: none; border: none; color: #f1f0ee; font-size: 24px; cursor: pointer;">&times;</button>
            <div id="global-size-modal-title"
                style="font-family: 'Playfair Display', serif; font-size: 22px; font-weight: 700; text-align: center; margin-bottom: 8px; color: #F0DDB8;">
                Tên sản phẩm</div>
            <p id="global-size-modal-subtitle"
                style="font-size: 14px; color: rgba(241, 240, 238, 0.72); text-align: center; margin-bottom: 20px;">Vui
                lòng chọn kích cỡ</p>

            <div id="global-size-modal-options"
                style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: center; margin-bottom: 24px;">
                <!-- Sizes will be injected here -->
            </div>

            <div id="global-temp-section" style="display: none; text-align: center; margin-bottom: 24px;">
                <p style="font-size: 14px; color: rgba(241, 240, 238, 0.72); margin-bottom: 12px;">Vui lòng chọn nhiệt
                    độ</p>
                <div style="display: flex; justify-content: center; gap: 12px;">
                    <button type="button" class="global-modal-temp-btn" data-temp="nóng"
                        onclick="selectGlobalModalTemp(this)"
                        style="padding: 8px 16px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.12); background: rgba(255,255,255,0.08); color: #f1f0ee; cursor: pointer; transition: all 0.2s; min-width: 80px;">Nóng</button>
                    <button type="button" class="global-modal-temp-btn" data-temp="lạnh"
                        onclick="selectGlobalModalTemp(this)"
                        style="padding: 8px 16px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.12); background: rgba(255,255,255,0.08); color: #f1f0ee; cursor: pointer; transition: all 0.2s; min-width: 80px;">Lạnh</button>
                </div>
            </div>

            <div style="margin-bottom: 20px; text-align: left;">
                <label for="global-size-modal-note"
                    style="display: block; font-size: 14px; color: rgba(241, 240, 238, 0.72); margin-bottom: 8px;">Ghi chú
                    (tuỳ chọn)</label>
                <textarea id="global-size-modal-note" rows="2" maxlength="255"
                    placeholder="VD: ít đường, ít đá, không hành..."
                    style="width: 100%; background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.14); color: #f1f0ee; border-radius: 10px; padding: 10px 12px; font-family: 'Outfit', sans-serif; font-size: 14px; outline: none; resize: vertical; box-sizing: border-box;"></textarea>
            </div>

            <button type="button" onclick="confirmGlobalSizeAndAdd()"
                style="width: 100%; padding: 12px 16px; border-radius: 999px; border: none; background: #c49a6c; color: #1a120c; font-weight: 600; letter-spacing: 0.02em; cursor: pointer; transition: background 0.3s;">Thêm
                món</button>
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
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
            document.getElementById('sidebar-overlay').classList.toggle('open');
        }

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

        function toggleNotif(event) {
            event.stopPropagation();
            var menu = document.getElementById('notif-dropdown');
            if (!menu) return;
            menu.classList.toggle('open');
        }

        window.isShowingAllNotifications = false;
        function loadAllNotifications(event) {
            event.preventDefault();
            var list = document.querySelector('#notif-dropdown .notification-dropdown-list');
            if (!list) return;

            list.innerHTML = '<div class="notification-empty">Đang tải tất cả thông báo...</div>';

            var url = '{{ route('staff.notifications.poll') }}?all=1';
            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
                .then(function (r) { return r.ok ? r.json() : null; })
                .then(function (data) {
                    if (!data) return;
                    updateClock(); // Just dummy
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
            var url = '{{ route('staff.notifications.poll') }}' + (showAll ? '?all=1' : '');

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
            var POLL_URL = '{{ route('staff.notifications.poll') }}';
            var INTERVAL = 10000; // 10 giây
            var inFlight = false;
            // Giá trị lúc tải trang — chuông kêu khi BẤT KỲ tín hiệu nào TĂNG:
            // thông báo mới, bàn rung mới (khách gọi tại bàn/dùng ngay), hoặc đơn mang về mới.
            var lastCount = {{ (int) $unreadNotificationCount }};
            var lastBanRung = {{ (int) ($banRungCount ?? 0) }};
            var lastTakeaway = {{ (int) ($takeawayQueueCount ?? 0) }};
            var lastActivity = null; // đơn/món mới do quản lý/nhân viên khác tạo (lấy mốc ở lần poll đầu)

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

            // Cập nhật badge "Đơn mang về" ở sidebar (số đơn mang về đang chờ giao).
            function updateTakeawayBadge(count) {
                var badge = document.getElementById('takeaway-badge');
                if (!badge) return;
                if (count > 0) {
                    badge.textContent = count > 99 ? 99 : count;
                    badge.style.display = '';
                } else {
                    badge.style.display = 'none';
                }
            }

            // Cập nhật badge "Bàn" ở sidebar (số bàn đang rung — khách gọi món mới chưa phục vụ).
            function updateBanRungBadge(count) {
                var badge = document.getElementById('ban-rung-badge');
                if (!badge) return;
                if (count > 0) {
                    badge.textContent = count > 99 ? 99 : count;
                    badge.style.display = '';
                } else {
                    badge.style.display = 'none';
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
                        var banRung = data.banRungCount || 0;
                        var takeaway = data.takeawayCount || 0;
                        var activity = data.activityCount || 0;
                        // Kêu chuông khi có đơn/việc MỚI ở BẤT KỲ hình thức nào:
                        // - bàn rung tăng (khách gọi tại bàn / dùng ngay / đặt bàn)
                        // - đơn mang về tăng
                        // - QUẢN LÝ hoặc nhân viên khác vừa tạo/bổ sung đơn (activity tăng)
                        // - thông báo mới bất kỳ
                        if (count > lastCount || banRung > lastBanRung || takeaway > lastTakeaway
                            || (lastActivity !== null && activity > lastActivity)) {
                            playNotifBell();
                        }
                        lastCount = count;
                        lastBanRung = banRung;
                        lastTakeaway = takeaway;
                        lastActivity = activity;
                        updateBadge(count);
                        updateTakeawayBadge(takeaway);
                        updateBanRungBadge(banRung);
                        var list = document.querySelector('#notif-dropdown .notification-dropdown-list');
                        if (list && typeof data.html === 'string') list.innerHTML = data.html;
                    })
                    .catch(function () { /* im lặng, tránh chặn UI */ })
                    .finally(function () { inFlight = false; });
            }

            setInterval(poll, INTERVAL);
        })();

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
                window.isShowingAllNotifications = false;
            }
        });

        // Auto-close alerts
        document.querySelectorAll('.alert').forEach(function (el) {
            setTimeout(function () {
                el.style.opacity = '0';
                el.style.transition = 'opacity 0.5s';
                setTimeout(function () { el.remove(); }, 500);
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
        // Global Size Modal Logic
        let globalProductData = null;

        function closeGlobalSizeModal() {
            document.getElementById('global-size-modal').style.display = 'none';
            globalProductData = null;
        }

        function selectGlobalModalSize(btn) {
            document.querySelectorAll('.global-modal-size-btn').forEach(b => {
                b.style.background = 'rgba(255,255,255,0.08)';
                b.style.borderColor = 'rgba(255,255,255,0.12)';
                b.style.color = '#f1f0ee';
                b.classList.remove('active');
            });
            btn.style.background = 'rgba(196, 154, 108, 0.2)';
            btn.style.borderColor = '#c49a6c';
            btn.style.color = '#F0DDB8';
            btn.classList.add('active');
            globalProductData.selectedSizeId = btn.dataset.sizeId;
        }

        function selectGlobalModalTemp(btn) {
            document.querySelectorAll('.global-modal-temp-btn').forEach(b => {
                b.style.background = 'rgba(255,255,255,0.08)';
                b.style.borderColor = 'rgba(255,255,255,0.12)';
                b.style.color = '#f1f0ee';
                b.classList.remove('active');
            });
            btn.style.background = 'rgba(196, 154, 108, 0.2)';
            btn.style.borderColor = '#c49a6c';
            btn.style.color = '#F0DDB8';
            btn.classList.add('active');
            globalProductData.selectedTemp = btn.dataset.temp;
        }

        function confirmGlobalSizeAndAdd() {
            if (!globalProductData) return;
            const sizeId = globalProductData.selectedSizeId;
            if (globalProductData.sizes.length > 0 && !sizeId) {
                showNotice('Vui lòng chọn kích cỡ!');
                return;
            }
            if (globalProductData.showTemp && !globalProductData.selectedTemp) {
                showNotice('Vui lòng chọn nhiệt độ!');
                return;
            }

            // Capture data before closing modal
            let form = globalProductData.form;
            let isShowTemp = globalProductData.showTemp;
            let selectedTemp = globalProductData.selectedTemp;
            let customNote = (document.getElementById('global-size-modal-note')?.value || '').trim();

            closeGlobalSizeModal();
            if (sizeId) {
                let sizeInput = document.createElement('input');
                sizeInput.type = 'hidden';
                sizeInput.name = 'size_id';
                sizeInput.value = sizeId;
                form.appendChild(sizeInput);
            }

            // Nhiệt độ là thuộc tính của món → gửi riêng qua 'nhiet_do' (backend ghép vào tên món).
            if (isShowTemp && selectedTemp) {
                let tempInput = document.createElement('input');
                tempInput.type = 'hidden';
                tempInput.name = 'nhiet_do';
                tempInput.value = selectedTemp;
                form.appendChild(tempInput);
            }

            // Ghi chú = ghi chú nhập tay của nhân viên (tách hoàn toàn khỏi nhiệt độ).
            if (customNote) {
                let noteInput = document.createElement('input');
                noteInput.type = 'hidden';
                noteInput.name = 'ghi_chu_mon';
                noteInput.value = customNote;
                form.appendChild(noteInput);
            }

            form.submit();
        }

        // Bật/tắt ô sửa ghi chú món ở bảng chi tiết bàn (icon bút chì).
        window.toggleNoteEdit = function (itemId) {
            var display = document.getElementById('note-display-' + itemId);
            var form = document.getElementById('note-form-' + itemId);
            if (!display || !form) return;
            var editing = form.style.display !== 'none';
            if (editing) {
                form.style.display = 'none';
                display.style.display = 'flex';
            } else {
                display.style.display = 'none';
                form.style.display = 'flex';
                var input = form.querySelector('input[name="ghi_chu_mon"]');
                if (input) { input.focus(); input.select(); }
            }
        };

        window.handleStaffAddItem = function (event, form) {
            event.preventDefault();

            const productName = form.dataset.productName;
            const nhietDo = form.dataset.nhietDo;
            let sizes = [];
            try {
                sizes = JSON.parse(form.dataset.sizes || '[]');
            } catch (e) { }

            if (sizes.length === 0 && !nhietDo) {
                // Nothing to select, just submit
                form.submit();
                return false;
            }

            globalProductData = {
                form: form,
                sizes: sizes,
                selectedSizeId: sizes.length > 0 ? sizes[0].id : null,
                showTemp: !!nhietDo,
                selectedTemp: null
            };

            const sizeOptionsContainer = document.getElementById('global-size-modal-options');
            const tempSection = document.getElementById('global-temp-section');
            const subtitle = document.getElementById('global-size-modal-subtitle');
            const noteInputEl = document.getElementById('global-size-modal-note');
            if (noteInputEl) noteInputEl.value = '';

            document.getElementById('global-size-modal-title').textContent = productName;

            if (globalProductData.showTemp) {
                tempSection.style.display = 'block';
                const temps = nhietDo.split(',').map(t => t.trim().toLowerCase());
                document.querySelectorAll('.global-modal-temp-btn').forEach(b => {
                    if (temps.includes(b.dataset.temp)) {
                        b.style.display = 'inline-block';
                    } else {
                        b.style.display = 'none';
                    }
                    b.style.background = 'rgba(255,255,255,0.08)';
                    b.style.borderColor = 'rgba(255,255,255,0.12)';
                    b.style.color = '#f1f0ee';
                    b.classList.remove('active');
                });

                if (temps.length === 1) {
                    document.querySelectorAll('.global-modal-temp-btn').forEach(b => {
                        if (b.dataset.temp === temps[0]) {
                            selectGlobalModalTemp(b);
                        }
                    });
                }
            } else {
                tempSection.style.display = 'none';
            }

            if (sizes.length > 0) {
                subtitle.style.display = 'block';
                let html = '';
                sizes.forEach((s, index) => {
                    const isActive = index === 0;
                    const bg = isActive ? 'rgba(196, 154, 108, 0.2)' : 'rgba(255,255,255,0.08)';
                    const border = isActive ? '#c49a6c' : 'rgba(255,255,255,0.12)';
                    const color = isActive ? '#F0DDB8' : '#f1f0ee';
                    const cls = isActive ? 'global-modal-size-btn active' : 'global-modal-size-btn';

                    const sizeDisplayName = s.code ? `${s.code} (${s.name})` : s.name;
                    html += `<button type="button" class="${cls}" data-size-id="${s.id}" onclick="selectGlobalModalSize(this)" style="padding: 10px 16px; border-radius: 8px; border: 1px solid ${border}; background: ${bg}; color: ${color}; cursor: pointer; transition: all 0.2s; min-width: 80px; display: flex; flex-direction: column; align-items: center; gap: 4px;">
                    <span style="font-weight: 600;">${sizeDisplayName}</span>
                </button>`;
                });
                sizeOptionsContainer.innerHTML = html;
            } else {
                subtitle.style.display = 'none';
                sizeOptionsContainer.innerHTML = '';
            }

            document.getElementById('global-size-modal').style.display = 'flex';
            return false;
        };
    </script>

    @include('partials.app-modals')
    @include('partials.flatpickr')
    @stack('scripts')
</body>

</html>