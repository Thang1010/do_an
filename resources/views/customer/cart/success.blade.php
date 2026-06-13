@extends('customer.layout.app')

@section('title', 'Đặt hàng thành công - XM Coffee')

@section('show_header', '0')
@section('show_footer', '0')

@section('content')
    <div
        style="min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;background:linear-gradient(135deg,#f5ede0 0%,#e8d5b0 100%);padding:40px 20px;font-family:'Outfit',sans-serif;">
        <div
            style="background:white;border-radius:24px;padding:48px 40px;max-width:520px;width:100%;box-shadow:0 20px 60px rgba(0,0,0,.12);text-align:center;">
            <div
                style="width:80px;height:80px;border-radius:50%;background:linear-gradient(135deg,#30261C,#8D5D5D);display:flex;align-items:center;justify-content:center;margin:0 auto 24px;">
                <svg width="40" height="40" fill="none" viewBox="0 0 24 24" stroke="white" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
            </div>

            <h1 style="font-family:'Playfair Display',serif;font-size:28px;font-weight:700;color:#30261C;margin:0 0 8px;">
                Đặt hàng thành công!</h1>
            <p style="color:#8D7E6E;margin:0 0 28px;font-size:15px;">Cảm ơn bạn đã tin tưởng XM Coffee ☕</p>

            @if(session('order_code'))
                <div style="background:#f5ede0;border-radius:12px;padding:16px 20px;margin-bottom:24px;">
                    <p style="font-size:13px;color:#8D7E6E;margin:0 0 4px;">Mã đơn hàng</p>
                    <p style="font-size:22px;font-weight:700;color:#30261C;letter-spacing:.05em;margin:0;">
                        {{ session('order_code') }}</p>
                </div>
            @endif

            @if(!empty(session('order_codes')))
                <div style="background:#f5ede0;border-radius:12px;padding:16px 20px;margin-bottom:24px;text-align:left;">
                    <p style="font-size:13px;color:#8D7E6E;margin:0 0 10px;">Các đơn hàng đã tạo:</p>
                    @foreach(session('order_codes') as $code)
                        <p style="font-size:17px;font-weight:700;color:#30261C;margin:4px 0;">{{ $code }}</p>
                    @endforeach
                </div>
            @endif

            <p style="font-size:14px;color:#a0927c;margin-bottom:28px;">Nhân viên của chúng tôi sẽ xác nhận đơn hàng sớm
                nhất có thể.</p>

            <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
                <a href="{{ route('menu.index') }}"
                    style="background:#30261C;color:#F0DDB8;padding:12px 28px;border-radius:50px;text-decoration:none;font-weight:600;font-size:15px;transition:background .2s;">
                    Tiếp tục mua sắm
                </a>
                @auth
                    <a href="{{ route('customer.orders') }}"
                        style="background:#f5ede0;color:#30261C;padding:12px 28px;border-radius:50px;text-decoration:none;font-weight:600;font-size:15px;">
                        Xem đơn hàng
                    </a>
                @endauth
            </div>
        </div>
    </div>
@endsection