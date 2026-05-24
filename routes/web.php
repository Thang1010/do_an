<?php

use App\Http\Controllers\Customer\HomeController;
use App\Http\Controllers\Customer\MenuController;
use App\Http\Controllers\Customer\CartController;
use App\Http\Controllers\Customer\ChatbotController;
use App\Http\Controllers\Customer\OrderController as CustomerOrderController;
use App\Http\Controllers\Webhooks\SepayWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — Entry Point
|--------------------------------------------------------------------------
| File này chỉ khai báo public routes và include các module route file.
| Chi tiết từng nhóm: routes/auth.php | routes/manager.php | routes/staff.php
*/

// ── Public / Customer routes ─────────────────────────────────────────────
Route::get('/', [HomeController::class, 'index'])->name('home');

// SePay webhook (thanh toan tu dong)
Route::post('/webhooks/sepay', [SepayWebhookController::class, 'handle'])->name('webhooks.sepay');

Route::get('/about',   fn () => view('customer.home.about'))->name('home.about');
Route::get('/contact', fn () => view('customer.home.contact'))->name('home.contact');

// Menu / Sản phẩm
Route::get('/menu',                [MenuController::class, 'index'])->name('menu.index');
Route::get('/menu/{id}',           [MenuController::class, 'show'])->name('menu.show');
Route::post('/menu/{id}/favorite', [MenuController::class, 'toggleFavorite'])->name('menu.favorite');

// Giỏ hàng
Route::get('/cart',           [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/add',      [CartController::class, 'add'])->name('cart.add');
Route::post('/cart/update',   [CartController::class, 'update'])->name('cart.update');
Route::post('/cart/note',     [CartController::class, 'updateNote'])->name('cart.note');
Route::post('/cart/remove',   [CartController::class, 'remove'])->name('cart.remove');
Route::get('/cart/count',     [CartController::class, 'count'])->name('cart.count');
Route::post('/cart/checkout', [CartController::class, 'checkout'])->name('cart.checkout');
Route::get('/cart/success',   [CartController::class, 'success'])->name('cart.success');
Route::get('/cart/payment/{orderCode}', [CartController::class, 'payment'])->name('cart.payment');
Route::post('/cart/payment/{orderCode}/confirm', [CartController::class, 'confirmPayment'])->name('cart.payment.confirm');
Route::post('/cart/cancel-guest', [CartController::class, 'cancelGuest'])->name('cart.cancel_guest');

// Chatbot
Route::get('/chatbot',           [ChatbotController::class, 'index'])->name('chatbot.index');
Route::post('/chatbot/message',  [ChatbotController::class, 'message'])->name('chatbot.message');
Route::post('/chatbot/suggest',  [ChatbotController::class, 'suggest'])->name('chatbot.suggest');

// Khách hàng thành viên
Route::prefix('customer')->name('customer.')->middleware('auth')->group(function () {
    Route::get('/profile', fn () => view('customer.profile.index'))->name('profile');
    Route::get('/orders', [CustomerOrderController::class, 'index'])->name('orders');
    Route::get('/orders/{id}', [CustomerOrderController::class, 'show'])->name('orders.show');
    Route::put('/orders/{id}', [CustomerOrderController::class, 'update'])->name('orders.update');
    Route::patch('/orders/{id}/cancel', [CustomerOrderController::class, 'cancel'])->name('orders.cancel');
    Route::post('/orders/{id}/edit-cart', [CustomerOrderController::class, 'editInCart'])->name('orders.edit_cart');
    Route::post('/vouchers/{id}/claim', [\App\Http\Controllers\Customer\VoucherController::class, 'claim'])->name('vouchers.claim');
});

// Newsletter
Route::post('/newsletter/subscribe', function () {
    return back()->with('success', 'Đăng ký thành công! Mã giảm giá 15% đã được gửi đến email của bạn.');
})->name('newsletter.subscribe');

// QR gọi món tại bàn
Route::get('/order/table/{table}', function ($table) {
    return view('customer.menu.index', ['tableNumber' => $table]);
})->name('order.table');

// ── Module routes ────────────────────────────────────────────────────────
require __DIR__ . '/auth.php';
require __DIR__ . '/manager.php';
require __DIR__ . '/staff.php';
