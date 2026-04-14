# Cấu trúc giao diện Cafe Tea - Hướng dẫn sử dụng

## 📁 Cấu trúc file đã tạo

### 1. **Layouts** (Bố cục chính)
- `resources/views/layouts/app.blade.php` - Layout cho trang public (khách hàng)
- `resources/views/layouts/admin.blade.php` - Layout cho trang admin/staff  
- `resources/views/layouts/auth.blade.php` - Layout cho trang đăng nhập/đăng ký

### 2. **Partials** (Các thành phần tái sử dụng)
- `resources/views/partials/header.blade.php` - Header public (logo, menu, giỏ hàng, user dropdown)
- `resources/views/partials/footer.blade.php` - Footer public (thông tin, liên kết, mạng xã hội)
- `resources/views/partials/admin-header.blade.php` - Header admin (thông báo, user profile)
- `resources/views/partials/admin-sidebar.blade.php` - Sidebar admin (menu quản trị với role-based)

### 3. **Views** (Trang mẫu)
- `resources/views/home.blade.php` - Trang chủ mẫu (hero, danh mục, sản phẩm, về chúng tôi)

### 4. **CSS Files**
- `public/css/app.css` - CSS cho giao diện public
- `public/css/admin.css` - CSS cho giao diện admin
- `public/css/auth.css` - CSS cho giao diện đăng nhập/đăng ký

### 5. **JavaScript** (Cần tạo)
- Tạo thư mục: `public/js/`
- Thêm file: `public/js/app.js` - JavaScript cho trang public
- Thêm file: `public/js/admin.js` - JavaScript cho trang admin

## 🎨 Tính năng đã tích hợp

### Public Layout (app.blade.php)
✅ Header responsive với:
- Logo và menu navigation
- Thanh tìm kiếm toggle
- Giỏ hàng với counter
- User dropdown (khi đã đăng nhập)
- Nút đăng nhập/đăng ký (khi chưa đăng nhập)
- Mobile menu toggle

✅ Footer với:
- 4 cột thông tin
- Social media links
- Quick links và danh mục
- Thông tin liên hệ

### Admin Layout (admin.blade.php)
✅ Sidebar với role-based menu:
- Admin: full access (dashboard, orders, products, customers, staff, reports, settings)
- Staff: limited access (orders, products only)
- Submenu cho sản phẩm
- Badge cho thông báo

✅ Admin header với:
- Sidebar toggle button
- Page title dynamic
- Notifications dropdown
- User profile dropdown

### Auth Layout (auth.blade.php)
✅ Clean layout cho đăng nhập/đăng ký:
- Gradient background
- Centered card design
- Logo và tiêu đề
- Form elements styled

## 📝 Hướng dẫn sử dụng

### 1. Tạo thư mục JavaScript
```bash
mkdir public/js
```

### 2. Tạo file app.js
Tạo file `public/js/app.js` với nội dung JavaScript để xử lý:
- Mobile menu toggle
- Search toggle  
- User dropdown
- Wishlist
- Add to cart
- Notifications

### 3. Tạo file admin.js
Tạo file `public/js/admin.js` với nội dung:
```javascript
document.addEventListener('DOMContentLoaded', function() {
    // Sidebar toggle
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.admin-sidebar');
    
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
    }
    
    // Dropdown toggles
    const notificationsToggle = document.getElementById('notificationsToggle');
    const notificationsMenu = document.getElementById('notificationsMenu');
    
    if (notificationsToggle) {
        notificationsToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            notificationsMenu.classList.toggle('active');
        });
    }
    
    const adminUserToggle = document.getElementById('adminUserToggle');
    const adminUserMenu = document.getElementById('adminUserMenu');
    
    if (adminUserToggle) {
        adminUserToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            adminUserMenu.classList.toggle('active');
        });
    }
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function() {
        document.querySelectorAll('.dropdown-menu').forEach(menu => {
            menu.classList.remove('active');
        });
    });
    
    // Submenu toggle
    const submenuLinks = document.querySelectorAll('.has-submenu');
    submenuLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            this.closest('.menu-item').classList.toggle('active');
        });
    });
});
```

### 4. Tạo routes
Cập nhật file `routes/web.php`:
```php
<?php

use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/', function () {
    return view('home');
});

Route::get('/menu', function () {
    return view('menu');
});

Route::get('/about', function () {
    return view('about');
});

Route::get('/contact', function () {
    return view('contact');
});

// Auth routes
Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::get('/register', function () {
    return view('auth.register');
});

// Admin routes (protected by auth middleware)
Route::middleware(['auth'])->prefix('admin')->group(function () {
    Route::get('/', function () {
        return view('admin.dashboard');
    });
    
    // Add more admin routes here
});
```

### 5. Tạo các view còn lại

#### Auth/Login (resources/views/auth/login.blade.php)
```blade
@extends('layouts.auth')

@section('title', 'Đăng nhập')

@section('content')
<div class="auth-card">
    <div class="auth-header">
        <div class="auth-logo">
            <img src="{{ asset('images/logo.png') }}" alt="Logo">
        </div>
        <h1 class="auth-title">Chào mừng trở lại</h1>
        <p class="auth-subtitle">Đăng nhập để tiếp tục</p>
    </div>
    
    <div class="auth-body">
        <form method="POST" action="{{ url('/login') }}">
            @csrf
            
            <div class="form-group">
                <label class="form-label">Email <span class="required">*</span></label>
                <div class="input-group">
                    <i class="input-icon fas fa-envelope"></i>
                    <input type="email" name="email" class="form-control" placeholder="your@email.com" required>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Mật khẩu <span class="required">*</span></label>
                <div class="input-group">
                    <i class="input-icon fas fa-lock"></i>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                    <button type="button" class="password-toggle">
                        <i class="far fa-eye"></i>
                    </button>
                </div>
            </div>
            
            <div class="form-check">
                <input type="checkbox" id="remember" name="remember" class="form-check-input">
                <label for="remember" class="form-check-label">Ghi nhớ đăng nhập</label>
            </div>
            
            <div class="forgot-password">
                <a href="{{ url('/forgot-password') }}" class="auth-link">Quên mật khẩu?</a>
            </div>
            
            <button type="submit" class="btn btn-primary">Đăng nhập</button>
        </form>
    </div>
    
    <div class="auth-footer">
        Chưa có tài khoản? <a href="{{ url('/register') }}" class="auth-link">Đăng ký ngay</a>
    </div>
</div>
@endsection
```

#### Admin Dashboard (resources/views/admin/dashboard.blade.php)
```blade
@extends('layouts.admin')

@section('page-title', 'Dashboard')

@section('content')
<div class="dashboard">
    <h1>Welcome to Admin Dashboard</h1>
    <!-- Add your dashboard content here -->
</div>
@endsection
```

## 🎯 Multi-Role System

Hệ thống đã được thiết kế để hỗ trợ nhiều vai trò:

### Roles được định nghĩa:
1. **Customer** (Khách hàng) - Sử dụng layout `app.blade.php`
2. **Staff** (Nhân viên) - Sử dụng layout `admin.blade.php` với menu giới hạn
3. **Admin** (Quản trị viên) - Sử dụng layout `admin.blade.php` với full menu

### Cách phân quyền trong blade:
```blade
@if(auth()->user()->role === 'admin')
    <!-- Admin only content -->
@elseif(auth()->user()->role === 'staff')
    <!-- Staff only content -->
@else
    <!-- Customer content -->
@endif
```

## 🌈 Color Scheme

### Public:
- Primary: #8B4513 (Nâu cafe)
- Secondary: #D2691E (Nâu sáng)

### Admin:
- Primary: #4e73df (Xanh dương)
- Success: #1cc88a (Xanh lá)
- Danger: #e74a3b (Đỏ)
- Warning: #f6c23e (Vàng)

## 📱 Responsive Design

Tất cả layout đều responsive với breakpoints:
- Desktop: > 992px
- Tablet: 768px - 992px
- Mobile: < 768px

## ⚙️ Tùy chỉnh thêm

### Thêm ảnh sản phẩm:
Đặt ảnh vào: `public/images/products/`
Đặt ảnh danh mục vào: `public/images/categories/`

### Thay đổi logo:
Thay file: `public/images/logo.png`

### Thêm fonts khác:
Cập nhật trong thẻ `<head>` của mỗi layout

## ✨ Lưu ý
- Đảm bảo đã cài đặt Laravel
- Chạy `php artisan serve` để test
- Tạo authentication system với Laravel Breeze hoặc Jetstream
- Cấu hình database và migrations cho users với role column

Chúc bạn thành công với dự án Cafe Tea! ☕
