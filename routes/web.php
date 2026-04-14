<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Guest\HomeController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// ===== Trang chủ (Khách vãng lai) =====
Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/about', function () {
    return view('home.about');
})->name('home.about');

Route::get('/contact', function () {
    return view('home.contact');
})->name('home.contact');

// ===== Menu / Sản phẩm =====
Route::get('/menu', function () {
    return view('menu.index');
})->name('menu.index');

Route::get('/menu/{id}', function ($id) {
    return view('menu.show', ['id' => $id]);
})->name('menu.show');

// ===== Giỏ hàng =====
Route::get('/cart', function () {
    return view('cart.index');
})->name('cart.index');

// ===== Chatbot =====
Route::get('/chatbot', function () {
    return view('chatbot.index');
})->name('chatbot.index');

// ===== Auth =====
Route::get('/login', [App\Http\Controllers\Auth\LoginController::class, 'showLoginForm'])
    ->name('auth.login')
    ->middleware('guest');

Route::post('/login', [App\Http\Controllers\Auth\LoginController::class, 'login'])
    ->name('auth.login.post');

Route::get('/register', [App\Http\Controllers\Auth\RegisterController::class, 'showRegisterForm'])
    ->name('auth.register')
    ->middleware('guest');

Route::post('/register', [App\Http\Controllers\Auth\RegisterController::class, 'register'])
    ->name('auth.register.post');

Route::post('/logout', [App\Http\Controllers\Auth\LoginController::class, 'logout'])
    ->name('auth.logout')
    ->middleware('auth');

Route::get('/forgot-password', function () {
    return view('auth.forgot-password');
})->name('auth.forgot-password')->middleware('guest');

Route::post('/forgot-password', function (\Illuminate\Http\Request $request) {
    // TODO: gửi email reset password
    return back()->with('status', 'Nếu tài khoản tồn tại, chúng tôi đã gửi hướng dẫn đến email/SĐT của bạn.');
})->name('auth.forgot-password.post')->middleware('guest');

// ===== Manager =====
use App\Http\Controllers\Manager\DashboardController;
use App\Http\Controllers\Manager\ProfileController as ManagerProfileController;
use App\Http\Controllers\Manager\NotificationController;
use App\Http\Controllers\Manager\ProductController;
use App\Http\Controllers\Manager\CategoryController;
use App\Http\Controllers\Manager\TableController;
use App\Http\Controllers\Manager\OrderController;
use App\Http\Controllers\Manager\UserController;
use App\Http\Controllers\Manager\ShiftController;
use App\Http\Controllers\Manager\PayrollController;
use App\Http\Controllers\Manager\InventoryController;
use App\Http\Controllers\Manager\ReportController;
use App\Http\Controllers\Manager\VoucherController;

Route::prefix('manager')->name('manager.')->middleware('auth')->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Personal profile
    Route::get('/profile', [ManagerProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ManagerProfileController::class, 'update'])->name('profile.update');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/{id}/open', [NotificationController::class, 'open'])->name('notifications.open');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');

    // Products
    Route::get('/products',                  [ProductController::class, 'index'])->name('products.index');
    Route::get('/products/create',           [ProductController::class, 'create'])->name('products.create');
    Route::post('/products',                 [ProductController::class, 'store'])->name('products.store');
    Route::get('/products/{id}/edit',        [ProductController::class, 'edit'])->name('products.edit');
    Route::put('/products/{id}',             [ProductController::class, 'update'])->name('products.update');
    Route::delete('/products/{id}',          [ProductController::class, 'destroy'])->name('products.destroy');
    Route::get('/products/{id}/images',      [ProductController::class, 'images'])->name('products.images');
    Route::post('/products/{id}/images',     [ProductController::class, 'storeImage'])->name('products.images.store');
    Route::delete('/products/{id}/images/{imgId}', [ProductController::class, 'destroyImage'])->name('products.images.destroy');
    Route::post('/products/{id}/status',     [ProductController::class, 'updateStatus'])->name('products.status');

    // Categories
    Route::get('/categories',        [CategoryController::class, 'index'])->name('categories.index');
    Route::post('/categories',       [CategoryController::class, 'store'])->name('categories.store');
    Route::put('/categories/{id}',   [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{id}',[CategoryController::class, 'destroy'])->name('categories.destroy');

    // Tables
    Route::get('/tables',            [TableController::class, 'index'])->name('tables.index');
    Route::get('/tables/{id}',       [TableController::class, 'show'])->name('tables.show');
    Route::post('/tables/{id}/payment-qr', [TableController::class, 'generatePaymentQr'])->name('tables.payment-qr');
    Route::patch('/tables/{id}/payment', [TableController::class, 'updateOrderPayment'])->name('tables.payment.update');
    Route::post('/tables',           [TableController::class, 'store'])->name('tables.store');
    Route::put('/tables/{id}',       [TableController::class, 'update'])->name('tables.update');
    Route::delete('/tables/{id}',    [TableController::class, 'destroy'])->name('tables.destroy');

    // Orders
    Route::get('/orders',               [OrderController::class, 'index'])->name('orders.index');
    Route::post('/orders',              [OrderController::class, 'store'])->name('orders.store');
    Route::get('/orders/{id}',          [OrderController::class, 'show'])->name('orders.show');
    Route::patch('/orders/{id}/status', [OrderController::class, 'updateStatus'])->name('orders.status');
    Route::patch('/orders/{id}/payment', [OrderController::class, 'updatePayment'])->name('orders.payment');

    // Users
    Route::get('/users/customers',         [UserController::class, 'customers'])->name('users.customers');
    Route::get('/users/staffs',            [UserController::class, 'staffs'])->name('users.staffs');
    Route::get('/users/staff',             [UserController::class, 'staffs'])->name('users.staff');
    Route::get('/users/admins',            [UserController::class, 'admins'])->name('users.admins');
    Route::post('/users',                  [UserController::class, 'store'])->name('users.store');
    Route::get('/users/{id}/edit',         [UserController::class, 'edit'])->name('users.edit');
    Route::get('/users/{id}',              [UserController::class, 'show'])->name('users.show');
    Route::get('/users/{id}/history',      [UserController::class, 'orderHistory'])->name('users.history');
    Route::patch('/users/{id}/role',       [UserController::class, 'updateRole'])->name('users.role.update');
    Route::patch('/users/{id}/toggle-lock',[UserController::class, 'toggleLock'])->name('users.toggle-lock');
    Route::delete('/users/{id}',           [UserController::class, 'destroy'])->name('users.destroy');

    // Shifts & Attendance
    Route::get('/shifts',            [ShiftController::class, 'index'])->name('shifts.index');
    Route::post('/shifts',           [ShiftController::class, 'store'])->name('shifts.store');
    Route::delete('/shifts/{id}',    [ShiftController::class, 'destroy'])->name('shifts.destroy');
    Route::get('/shifts/attendance', [ShiftController::class, 'attendance'])->name('shifts.attendance');
    Route::post('/shifts/attendance',[ShiftController::class, 'storeAttendance'])->name('shifts.attendance.store');

    // Payroll
    Route::get('/payroll',           [PayrollController::class, 'index'])->name('payroll.index');
    Route::post('/payroll/generate', [PayrollController::class, 'generate'])->name('payroll.generate');
    Route::get('/payroll/export',    [PayrollController::class, 'export'])->name('payroll.export');

    // Inventory
    Route::get('/inventory',         [InventoryController::class, 'index'])->name('inventory.index');
    Route::get('/inventory/import',  [InventoryController::class, 'import'])->name('inventory.import');
    Route::post('/inventory/import', [InventoryController::class, 'storeImport'])->name('inventory.import.store');
    Route::get('/inventory/export',  [InventoryController::class, 'export'])->name('inventory.export');
    Route::post('/inventory/export', [InventoryController::class, 'storeExport'])->name('inventory.export.store');

    // Reports
    Route::get('/reports/revenue',   [ReportController::class, 'revenue'])->name('reports.revenue');
    Route::get('/reports/orders',    [ReportController::class, 'orders'])->name('reports.orders');
    Route::get('/reports/products',  [ReportController::class, 'products'])->name('reports.products');
    Route::get('/reports/staff',     [ReportController::class, 'staff'])->name('reports.staff');
    Route::get('/reports/inventory', [ReportController::class, 'inventory'])->name('reports.inventory');
    Route::get('/reports/points',    [ReportController::class, 'points'])->name('reports.points');

    // Vouchers
    Route::get('/vouchers',          [VoucherController::class, 'index'])->name('vouchers.index');
    Route::get('/vouchers/create',   [VoucherController::class, 'create'])->name('vouchers.create');
    Route::post('/vouchers',         [VoucherController::class, 'store'])->name('vouchers.store');
    Route::get('/vouchers/{id}/edit',[VoucherController::class, 'edit'])->name('vouchers.edit');
    Route::get('/vouchers/{id}',     [VoucherController::class, 'show'])->name('vouchers.show');
    Route::get('/vouchers/{id}/export-users', [VoucherController::class, 'exportUsers'])->name('vouchers.export-users');
    Route::put('/vouchers/{id}',     [VoucherController::class, 'update'])->name('vouchers.update');
    Route::delete('/vouchers/{id}',  [VoucherController::class, 'destroy'])->name('vouchers.destroy');
});




// ===== Staff Dashboard =====
Route::get('/staff/dashboard', function () {
    return view('staff.dashboard');
})->name('staff.dashboard')->middleware('auth');

// ===== Khách hàng thành viên =====
Route::prefix('customer')->name('customer.')->middleware('auth')->group(function () {
    Route::get('/profile', function () {
        return view('customer.profile');
    })->name('profile');

    Route::get('/orders', function () {
        return view('customer.orders');
    })->name('orders');

    Route::get('/points', function () {
        return view('customer.points');
    })->name('points');
});

// ===== Newsletter =====
Route::post('/newsletter/subscribe', function () {
    // Xử lý đăng ký newsletter
    return back()->with('success', 'Đăng ký thành công! Mã giảm giá 15% đã được gửi đến email của bạn.');
})->name('newsletter.subscribe');

// ===== QR Order (gọi món tại bàn) =====
Route::get('/order/table/{table}', function ($table) {
    return view('menu.index', ['tableNumber' => $table]);
})->name('order.table');
