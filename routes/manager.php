<?php

use App\Http\Controllers\Manager\DashboardController;
use App\Http\Controllers\Manager\ProfileController as ManagerProfileController;
use App\Http\Controllers\Manager\NotificationController;
use App\Http\Controllers\Manager\ProductController;
use App\Http\Controllers\Manager\CategoryController;
use App\Http\Controllers\Manager\TableController;
use App\Http\Controllers\Manager\OrderController;
use App\Http\Controllers\Manager\PositionController;
use App\Http\Controllers\Manager\UserController;
use App\Http\Controllers\Manager\ShiftController;
use App\Http\Controllers\Manager\InventoryController;
use App\Http\Controllers\Manager\IngredientController;
use App\Http\Controllers\Manager\ReportController;
use App\Http\Controllers\Manager\VoucherController;
use App\Http\Controllers\Manager\ExpenseController;
use App\Http\Controllers\Manager\ShiftCloseController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Manager Routes
|--------------------------------------------------------------------------
| Tất cả routes dành cho vai trò: quản lý, chủ cửa hàng
*/

Route::prefix('manager')->name('manager.')->middleware(['auth', 'role:quản lý,chủ cửa hàng'])->group(function () {

    // ── Dashboard & Profile ───────────────────────────────────────────
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [ManagerProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ManagerProfileController::class, 'update'])->name('profile.update');

    // ── Notifications ─────────────────────────────────────────────────
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/{id}/open', [NotificationController::class, 'open'])->name('notifications.open');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');

    // ── Products ──────────────────────────────────────────────────────
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
    Route::get('/products/recipes/excel', [ProductController::class, 'exportRecipesExcel'])->name('products.recipes.excel');
    Route::post('/products', [ProductController::class, 'store'])->name('products.store');
    Route::get('/products/{id}/edit', [ProductController::class, 'edit'])->name('products.edit');
    Route::put('/products/{id}', [ProductController::class, 'update'])->name('products.update');
    Route::delete('/products/{id}', [ProductController::class, 'destroy'])->name('products.destroy');
    Route::get('/products/{id}/images', [ProductController::class, 'images'])->name('products.images');
    Route::post('/products/{id}/images', [ProductController::class, 'storeImage'])->name('products.images.store');
    Route::delete('/products/{id}/images/{imgId}', [ProductController::class, 'destroyImage'])->name('products.images.destroy');
    Route::post('/products/{id}/status', [ProductController::class, 'updateStatus'])->name('products.status');

    // ── Categories ────────────────────────────────────────────────────
    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::get('/categories/{id}', [CategoryController::class, 'show'])->name('categories.show');
    Route::put('/categories/{id}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{id}', [CategoryController::class, 'destroy'])->name('categories.destroy');

    // ── Tables ────────────────────────────────────────────────────────
    Route::get('/tables', [TableController::class, 'index'])->name('tables.index');
    Route::get('/tables/{id}', [TableController::class, 'show'])->name('tables.show');

    Route::post('/tables/{id}/add-item', [TableController::class, 'addItem'])->name('tables.add-item');
    Route::patch('/tables/{id}/payment', [TableController::class, 'updateOrderPayment'])->name('tables.payment.update');
    Route::patch('/tables/{id}/enter', [TableController::class, 'enterTable'])->name('tables.enter');
    Route::patch('/tables/{id}/release', [TableController::class, 'releaseTable'])->name('tables.release');
    Route::delete('/tables/{id}/clear', [TableController::class, 'clearTable'])->name('tables.clear');
    Route::post('/tables', [TableController::class, 'store'])->name('tables.store');
    Route::put('/tables/{id}', [TableController::class, 'update'])->name('tables.update');
    Route::delete('/tables/{id}', [TableController::class, 'destroy'])->name('tables.destroy');

    // ── Orders ────────────────────────────────────────────────────────
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
    Route::get('/orders/{id}/edit', [OrderController::class, 'edit'])->name('orders.edit');
    Route::put('/orders/{id}', [OrderController::class, 'update'])->name('orders.update');
    Route::get('/orders/{id}', [OrderController::class, 'show'])->name('orders.show');
    Route::delete('/orders/{id}', [OrderController::class, 'destroy'])->name('orders.destroy');
    Route::patch('/orders/{id}/payment', [OrderController::class, 'updatePayment'])->name('orders.payment');


    // ── Positions ─────────────────────────────────────────────────────
    Route::get('/positions', [PositionController::class, 'index'])->name('positions.index');
    Route::get('/positions/create', [PositionController::class, 'create'])->name('positions.create');
    Route::post('/positions', [PositionController::class, 'store'])->name('positions.store');
    Route::get('/positions/{id}/edit', [PositionController::class, 'edit'])->name('positions.edit');
    Route::put('/positions/{id}', [PositionController::class, 'update'])->name('positions.update');
    Route::delete('/positions/{id}', [PositionController::class, 'destroy'])->name('positions.destroy');
    Route::get('/positions/{id}', [PositionController::class, 'show'])->name('positions.show');
    Route::post('/positions/{id}/assign', [PositionController::class, 'assignProfiles'])->name('positions.assign');
    Route::delete('/positions/{id}/remove-profile/{profileId}', [PositionController::class, 'removeProfile'])->name('positions.remove-profile');

    // ── Users ─────────────────────────────────────────────────────────
    Route::get('/users/customers', [UserController::class, 'customers'])->name('users.customers');
    Route::get('/users/staffs', [UserController::class, 'staffs'])->name('users.staffs');
    Route::get('/users/staff', [UserController::class, 'staffs'])->name('users.staff');
    Route::get('/users/admins', [UserController::class, 'admins'])->middleware('role:chủ cửa hàng')->name('users.admins');
    Route::get('/users/pending-approvals', [UserController::class, 'pendingApprovals'])->name('users.pending-approvals');
    Route::post('/users/pending-approvals/confirm', [UserController::class, 'bulkConfirmAccounts'])->name('users.pending-approvals.confirm');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::get('/users/{id}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::get('/users/{id}', [UserController::class, 'show'])->name('users.show');
    Route::get('/users/{id}/history', [UserController::class, 'orderHistory'])->name('users.history');
    Route::patch('/users/{id}/role', [UserController::class, 'updateRole'])->name('users.role.update');
    Route::patch('/users/{id}/toggle-lock', [UserController::class, 'toggleLock'])->name('users.toggle-lock');
    Route::delete('/users/{id}', [UserController::class, 'destroy'])->name('users.destroy');

    // ── Shifts & Attendance ───────────────────────────────────────────
    Route::get('/shifts', [ShiftController::class, 'index'])->name('shifts.index');
    Route::get('/shifts/create', [ShiftController::class, 'create'])->name('shifts.create');
    Route::get('/shifts/attendance', [ShiftController::class, 'attendance'])->name('shifts.attendance');
    Route::get('/shifts/attendance/export-payroll', [ShiftController::class, 'exportPayroll'])->name('shifts.attendance.export-payroll');
    Route::post('/shifts', [ShiftController::class, 'store'])->name('shifts.store');
    Route::post('/shifts/attendance', [ShiftController::class, 'storeAttendance'])->name('shifts.attendance.store');
    Route::post('/shifts/send-next-week', [ShiftController::class, 'sendNextWeekSchedule'])->name('shifts.send-next-week');
    Route::get('/shifts/{id}/edit', [ShiftController::class, 'edit'])->name('shifts.edit');
    Route::get('/shifts/{id}', [ShiftController::class, 'show'])->name('shifts.show');
    Route::put('/shifts/{id}', [ShiftController::class, 'update'])->name('shifts.update');
    Route::delete('/shifts/{id}', [ShiftController::class, 'destroy'])->name('shifts.destroy');
    Route::put('/shifts/attendance/{id}', [ShiftController::class, 'updateAttendance'])->name('shifts.attendance.update');
    Route::delete('/shifts/attendance/{id}', [ShiftController::class, 'destroyAttendance'])->name('shifts.attendance.destroy');
    Route::post('/shifts/{id}/force-checkin/{userId}', [ShiftController::class, 'forceCheckin'])->name('shifts.force-checkin');
    Route::post('/shifts/{id}/force-checkout/{userId}', [ShiftController::class, 'forceCheckout'])->name('shifts.force-checkout');

    // ── Ingredients ───────────────────────────────────────────────────
    Route::get('/ingredients', [IngredientController::class, 'index'])->name('ingredients.index');
    Route::get('/ingredients/create', [IngredientController::class, 'create'])->name('ingredients.create');
    Route::post('/ingredients', [IngredientController::class, 'store'])->name('ingredients.store');
    Route::get('/ingredients/{id}/edit', [IngredientController::class, 'edit'])->middleware('role:chủ cửa hàng')->name('ingredients.edit');
    Route::put('/ingredients/{id}', [IngredientController::class, 'update'])->middleware('role:chủ cửa hàng')->name('ingredients.update');
    Route::delete('/ingredients/{id}', [IngredientController::class, 'destroy'])->middleware('role:chủ cửa hàng')->name('ingredients.destroy');

    // ── Ingredient Requests ───────────────────────────────────────────
    Route::get('/ingredient-requests', [IngredientController::class, 'requestsIndex'])->name('ingredients.requests.index');
    Route::get('/ingredient-requests/{id}', [IngredientController::class, 'requestShow'])->name('ingredients.requests.show');
    Route::post('/ingredient-requests', [IngredientController::class, 'storeRequest'])->name('ingredients.requests.store');
    Route::post('/ingredient-requests/{id}/approve', [IngredientController::class, 'approveRequest'])->middleware('role:chủ cửa hàng')->name('ingredients.requests.approve');
    Route::post('/ingredient-requests/{id}/reject', [IngredientController::class, 'rejectRequest'])->middleware('role:chủ cửa hàng')->name('ingredients.requests.reject');

    // ── Expenses ──────────────────────────────────────────────────────
    Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses.index');
    Route::get('/expenses/create', [ExpenseController::class, 'create'])->name('expenses.create');
    Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store');

    // ── Salary ────────────────────────────────────────────────────────
    Route::get('/salary/export', [\App\Http\Controllers\Manager\SalaryController::class, 'export'])->name('salary.export');
    Route::get('/salary', [\App\Http\Controllers\Manager\SalaryController::class, 'index'])->name('salary.index');
    Route::get('/salary/{id}', [\App\Http\Controllers\Manager\SalaryController::class, 'show'])->name('salary.show');
    Route::get('/salary/{id}/edit', [\App\Http\Controllers\Manager\SalaryController::class, 'edit'])->name('salary.edit');
    Route::put('/salary/{id}', [\App\Http\Controllers\Manager\SalaryController::class, 'update'])->name('salary.update');

    // ── Inventory ─────────────────────────────────────────────────────
    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::get('/inventory/import', [InventoryController::class, 'import'])->name('inventory.import');
    Route::post('/inventory/import', [InventoryController::class, 'storeImport'])->name('inventory.import.store');
    Route::get('/inventory/export', [InventoryController::class, 'export'])->name('inventory.export');
    Route::post('/inventory/export', [InventoryController::class, 'storeExport'])->name('inventory.export.store');
    Route::post('/inventory/export-excel', [InventoryController::class, 'storeExportExcel'])->name('inventory.export.excel');
    Route::post('/inventory/adjustment', [InventoryController::class, 'storeAdjustment'])->name('inventory.adjustment.store');
    Route::patch('/inventory/stock', [InventoryController::class, 'updateStock'])->name('inventory.stock.update');
    Route::get('/inventory/stock/excel', [InventoryController::class, 'exportStockExcel'])->name('inventory.stock.excel');
    Route::get('/inventory/history/import/excel', [InventoryController::class, 'exportImportHistoryExcel'])->name('inventory.history.import.excel');
    Route::get('/inventory/history/export/excel', [InventoryController::class, 'exportExportHistoryExcel'])->name('inventory.history.export.excel');

    // ── Shift Close ───────────────────────────────────────────────────
    Route::get('/shift-close', [ShiftCloseController::class, 'index'])->name('shift-close.index');
    Route::post('/shift-close/start', [ShiftCloseController::class, 'startShift'])->name('shift-close.start');
    Route::post('/shift-close', [ShiftCloseController::class, 'store'])->name('shift-close.store');

    // ── Reports ───────────────────────────────────────────────────────
    Route::get('/reports/revenue', [ReportController::class, 'revenue'])->name('reports.revenue');
    Route::get('/reports/revenue/export', [ReportController::class, 'exportRevenueExcel'])->name('reports.revenue.export');


    // ── Vouchers ──────────────────────────────────────────────────────
    Route::get('/vouchers', [VoucherController::class, 'index'])->name('vouchers.index');
    Route::get('/vouchers/create', [VoucherController::class, 'create'])->name('vouchers.create');
    Route::post('/vouchers', [VoucherController::class, 'store'])->name('vouchers.store');
    Route::get('/vouchers/{id}/edit', [VoucherController::class, 'edit'])->name('vouchers.edit');
    Route::get('/vouchers/{id}', [VoucherController::class, 'show'])->name('vouchers.show');
    Route::get('/vouchers/{id}/export-users', [VoucherController::class, 'exportUsers'])->name('vouchers.export-users');
    Route::put('/vouchers/{id}', [VoucherController::class, 'update'])->name('vouchers.update');
    Route::delete('/vouchers/{id}', [VoucherController::class, 'destroy'])->name('vouchers.destroy');
});

// ── Standalone manager routes (đặc biệt — không nằm trong prefix group) ──
Route::post('/account-approvals/{id}/confirm', [UserController::class, 'confirmAccount'])
    ->name('account-approvals.confirm')
    ->middleware(['auth', 'role:chủ cửa hàng,quản lý']);

Route::get('/shift-checkin/{id}', [ShiftController::class, 'scanCheckIn'])
    ->name('shifts.checkin.scan')
    ->middleware(['auth', 'signed', 'role:nhân viên,quản lý,chủ cửa hàng']);
