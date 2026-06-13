<?php

use App\Http\Controllers\Customer\CartController;
use App\Http\Controllers\Customer\ChatbotController;
use App\Http\Controllers\Customer\HomeController;
use App\Http\Controllers\Customer\MenuController;
use App\Http\Controllers\Customer\OrderController as CustomerOrderController;
use App\Http\Controllers\Customer\ProfileController;
use App\Http\Controllers\Customer\VoucherController;
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

Route::view('/about', 'customer.home.about')->name('home.about');
Route::view('/contact', 'customer.home.contact')->name('home.contact');

// Menu / Sản phẩm
Route::get('/menu', [MenuController::class, 'index'])->name('menu.index');
Route::get('/menu/{id}', [MenuController::class, 'show'])->name('menu.show');
Route::post('/menu/{id}/favorite', [MenuController::class, 'toggleFavorite'])->name('menu.favorite');
Route::post('/menu/{id}/review', [MenuController::class, 'storeReview'])->name('menu.review.store');

// Giỏ hàng
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
Route::post('/cart/update', [CartController::class, 'update'])->name('cart.update');
Route::post('/cart/note', [CartController::class, 'updateNote'])->name('cart.note');
Route::post('/cart/remove', [CartController::class, 'remove'])->name('cart.remove');
Route::get('/cart/count', [CartController::class, 'count'])->name('cart.count');
Route::post('/cart/checkout', [CartController::class, 'checkout'])->name('cart.checkout');
Route::get('/cart/success', [CartController::class, 'success'])->name('cart.success');
Route::get('/cart/payment/{orderCode}', [CartController::class, 'payment'])->name('cart.payment');
Route::get('/cart/payment/{orderCode}/status', [CartController::class, 'paymentStatusAjax'])->name('cart.payment.status');
Route::get('/cart/payment/{orderCode}/payos-return', [CartController::class, 'payosReturn'])->name('cart.payos.return');
Route::get('/cart/payment/{orderCode}/payos-cancel', [CartController::class, 'payosCancel'])->name('cart.payos.cancel');
Route::post('/cart/payment/{orderCode}/confirm', [CartController::class, 'confirmPayment'])->name('cart.payment.confirm');
Route::post('/cart/cancel', [CartController::class, 'cancelGuest'])->name('cart.cancel_guest');

// Chatbot
Route::get('/chatbot', [ChatbotController::class, 'index'])->name('chatbot.index');
Route::post('/chatbot/message', [ChatbotController::class, 'message'])->name('chatbot.message');
Route::post('/chatbot/suggest', [ChatbotController::class, 'suggest'])->name('chatbot.suggest');
Route::get('/chatbot/sessions', [ChatbotController::class, 'sessionList'])->name('chatbot.sessions');
Route::post('/chatbot/sessions/reset', [ChatbotController::class, 'sessionReset'])->name('chatbot.sessions.reset');
Route::get('/chatbot/sessions/{id}', [ChatbotController::class, 'sessionMessages'])->whereNumber('id')->name('chatbot.session.messages');

// Khách hàng thành viên
Route::prefix('customer')->name('customer.')->middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/profile/password', [ProfileController::class, 'editPassword'])->name('profile.password.edit');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::get('/orders', [CustomerOrderController::class, 'index'])->name('orders');
    Route::get('/orders/{id}', [CustomerOrderController::class, 'show'])->name('orders.show');
    Route::put('/orders/{id}', [CustomerOrderController::class, 'update'])->name('orders.update');
    Route::post('/orders/{id}/edit-cart', [CustomerOrderController::class, 'editInCart'])->name('orders.edit_cart');
    Route::get('/chat-history', [ChatbotController::class, 'history'])->name('chat_history');
    Route::get('/chat-history/{id}', [ChatbotController::class, 'historyShow'])->name('chat_history.show');
    Route::post('/vouchers/{id}/claim', [VoucherController::class, 'claim'])->name('vouchers.claim');
    Route::post('/vouchers/claim-all', [VoucherController::class, 'claimAll'])->name('vouchers.claim-all');
});

// Newsletter
Route::post('/newsletter/subscribe', [HomeController::class, 'newsletter'])->name('newsletter.subscribe');

// QR gọi món tại bàn
Route::get('/order/table/{table}', [HomeController::class, 'orderTable'])->name('order.table');

// ── Module routes ────────────────────────────────────────────────────────
require __DIR__.'/auth.php';
require __DIR__.'/manager.php';
require __DIR__.'/staff.php';
