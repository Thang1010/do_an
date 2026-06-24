<?php

use App\Http\Controllers\Staff\DashboardController as StaffDashboardController;
use App\Http\Controllers\Staff\TableController as StaffTableController;
use App\Http\Controllers\Staff\OrderController as StaffOrderController;
use App\Http\Controllers\Staff\TakeawayController as StaffTakeawayController;

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
    Route::patch('/tables/{id}/enter',              [StaffTableController::class, 'enterTable'])->name('tables.enter');
    Route::patch('/tables/{id}/release',            [StaffTableController::class, 'releaseTable'])->name('tables.release');
    Route::delete('/tables/{id}/clear',             [StaffTableController::class, 'clearTable'])->name('tables.clear');
    Route::get('/tables/{id}',                      [StaffTableController::class, 'show'])->name('tables.show');

    Route::patch('/tables/{id}/payment',            [StaffTableController::class, 'updatePayment'])->name('tables.payment.update');
    Route::post('/tables/{id}/add-item',            [StaffTableController::class, 'addItem'])->name('tables.add-item');
    Route::patch('/tables/{id}/update-item/{itemId}',[StaffTableController::class, 'updateItemQuantity'])->name('tables.update-item');
    Route::patch('/tables/{id}/order',              [StaffTableController::class, 'updateOrderStatus'])->name('tables.order.update');

    // ── Takeaway (Đơn mang về) — hàng đợi đơn online không gắn bàn ──
    Route::get('/takeaway',                  [StaffTakeawayController::class, 'index'])->name('takeaway.index');
    Route::patch('/takeaway/{id}/delivered', [StaffTakeawayController::class, 'markDelivered'])->name('takeaway.delivered');

    // ── Orders (Đơn hàng) ────────────────────────────────────────────
    Route::get('/orders',                   [StaffOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{id}',              [StaffOrderController::class, 'show'])->name('orders.show');

    Route::patch('/orders/{id}/payment',    [StaffOrderController::class, 'updatePayment'])->name('orders.payment.update');


    // ── Shifts (Ca làm việc) ─────────────────────────────────────────
    Route::get('/shifts',          [StaffShiftController::class, 'index'])->name('shifts.index');
    Route::get('/shifts/export',   [StaffShiftController::class, 'exportSchedule'])->name('shifts.export');
    Route::get('/shifts/{id}',     [StaffShiftController::class, 'show'])->name('shifts.show');
    Route::post('/shifts/checkin', [StaffShiftController::class, 'checkin'])->name('shifts.checkin');
    Route::post('/shifts/checkout',[StaffShiftController::class, 'checkout'])->name('shifts.checkout');
    Route::post('/shifts/start-cash', [StaffShiftController::class, 'startCash'])->name('shifts.start-cash');

    // ── Expenses (Chi tiêu) ──────────────────────────────────────────
    Route::get('/expenses',        [StaffExpenseController::class, 'index'])->name('expenses.index');
    Route::get('/expenses/create', [StaffExpenseController::class, 'create'])->name('expenses.create');
    Route::post('/expenses',       [StaffExpenseController::class, 'store'])->name('expenses.store');

    // ── Notifications ────────────────────────────────────────────────
    Route::get('/notifications',             [StaffNotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/poll',        [StaffNotificationController::class, 'poll'])->name('notifications.poll');
    Route::get('/notifications/{id}/open',   [StaffNotificationController::class, 'open'])->name('notifications.open');
    Route::post('/notifications/read-all',   [StaffNotificationController::class, 'markAllRead'])->name('notifications.read-all');

    // ── Profile ──────────────────────────────────────────────────────
    Route::get('/profile',          [StaffProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile',          [StaffProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [StaffProfileController::class, 'updatePassword'])->name('profile.password');
});
