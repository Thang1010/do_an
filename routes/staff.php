<?php

use App\Http\Controllers\Staff\DashboardController as StaffDashboardController;
use App\Http\Controllers\Staff\TableController as StaffTableController;
use App\Http\Controllers\Staff\OrderController as StaffOrderController;
use App\Http\Controllers\Staff\CustomerController as StaffCustomerController;
use App\Http\Controllers\Staff\ShiftController as StaffShiftController;
use App\Http\Controllers\Staff\ExpenseController as StaffExpenseController;
use App\Http\Controllers\Staff\NotificationController as StaffNotificationController;
use App\Http\Controllers\Staff\ProfileController as StaffProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Staff Routes
|--------------------------------------------------------------------------
| Tất cả routes dành cho vai trò: nhân viên
*/

Route::prefix('staff')->name('staff.')->middleware(['auth', 'role:nhân viên'])->group(function () {

    // ── Dashboard ────────────────────────────────────────────────────
    Route::get('/dashboard', [StaffDashboardController::class, 'index'])->name('dashboard');

    // ── Tables (Bàn) — màn hình POS chính ───────────────────────────
    Route::get('/tables',                           [StaffTableController::class, 'index'])->name('tables.index');
    Route::post('/tables/{id}/assign-order',        [StaffTableController::class, 'assignOrder'])->name('tables.assign-order');
    Route::get('/tables/{id}',                      [StaffTableController::class, 'show'])->name('tables.show');
    Route::post('/tables/{id}/payment-qr',          [StaffTableController::class, 'generatePaymentQr'])->name('tables.payment-qr');
    Route::patch('/tables/{id}/payment',            [StaffTableController::class, 'updatePayment'])->name('tables.payment.update');
    Route::post('/tables/{id}/add-item',            [StaffTableController::class, 'addItem'])->name('tables.add-item');
    Route::patch('/tables/{id}/update-item/{itemId}',[StaffTableController::class, 'updateItemQuantity'])->name('tables.update-item');
    Route::patch('/tables/{id}/order',              [StaffTableController::class, 'updateOrderStatus'])->name('tables.order.update');

    // ── Orders (Đơn hàng) ────────────────────────────────────────────
    Route::get('/orders',                   [StaffOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{id}',              [StaffOrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{id}/payment-qr',  [StaffOrderController::class, 'generatePaymentQr'])->name('orders.payment-qr');
    Route::patch('/orders/{id}/payment',    [StaffOrderController::class, 'updatePayment'])->name('orders.payment.update');

    // ── Customers (Khách hàng) ───────────────────────────────────────
    Route::get('/customers',                        [StaffCustomerController::class, 'index'])->name('customers.index');
    Route::post('/customers/{id}/password-reset',   [StaffCustomerController::class, 'requestPasswordReset'])->name('customers.password-reset');

    // ── Shifts (Ca làm việc) ─────────────────────────────────────────
    Route::get('/shifts',          [StaffShiftController::class, 'index'])->name('shifts.index');
    Route::get('/shifts/export',   [StaffShiftController::class, 'exportSchedule'])->name('shifts.export');
    Route::get('/shifts/{id}',     [StaffShiftController::class, 'show'])->name('shifts.show');
    Route::post('/shifts/checkin', [StaffShiftController::class, 'checkin'])->name('shifts.checkin');
    Route::post('/shifts/checkout',[StaffShiftController::class, 'checkout'])->name('shifts.checkout');

    // ── Expenses (Chi tiêu) ──────────────────────────────────────────
    Route::get('/expenses',        [StaffExpenseController::class, 'index'])->name('expenses.index');
    Route::get('/expenses/create', [StaffExpenseController::class, 'create'])->name('expenses.create');
    Route::post('/expenses',       [StaffExpenseController::class, 'store'])->name('expenses.store');

    // ── Notifications ────────────────────────────────────────────────
    Route::get('/notifications',             [StaffNotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/{id}/open',   [StaffNotificationController::class, 'open'])->name('notifications.open');
    Route::post('/notifications/read-all',   [StaffNotificationController::class, 'markAllRead'])->name('notifications.read-all');

    // ── Profile ──────────────────────────────────────────────────────
    Route::get('/profile',          [StaffProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile',          [StaffProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [StaffProfileController::class, 'updatePassword'])->name('profile.password');
});
