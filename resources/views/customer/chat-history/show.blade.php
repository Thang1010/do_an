@extends('customer.layout.app')

@section('title', 'Chi tiết trò chuyện - XM Coffee')
@section('meta_description', 'Xem lại nội dung cuộc trò chuyện với trợ lý XM Coffee.')

@section('header_overlay', 'bg-black/30')
@section('body_class', 'cart-page')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/cart.css') }}">
<link rel="stylesheet" href="{{ asset('css/chatbot-page.css') }}">
@endpush

@section('content')
	<main class="cart-main">
		<div class="cart-container">
			<div style="margin-top: 12px; margin-bottom: 40px; background: rgba(30, 17, 6, 0.5); border-radius: 24px; padding: 28px 24px; border: 1px solid rgba(255, 255, 255, 0.12); backdrop-filter: blur(14px);">

				{{-- Tiêu đề --}}
				<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
					<a href="{{ route('customer.chat_history') }}" class="cart-submit-btn" style="padding: 8px 16px; width: auto; margin: 0; font-size: 0.875rem; background: rgba(255,255,255,0.14); color: #fff; text-decoration: none;">&lsaquo; Quay lại</a>
					<div style="text-align: center; flex: 1; min-width: 200px;">
						<h2 style="font-family: 'Playfair Display', serif; font-size: 22px; font-weight: 700; color: #fff; margin: 0 0 4px;">Cuộc trò chuyện</h2>
						<p style="font-size: 0.8rem; color: rgba(255,255,255,0.55); margin: 0;">{{ $session->created_at?->format('d/m/Y H:i') }} · {{ $session->tinNhanChat->count() }} tin nhắn</p>
					</div>
					<a href="{{ route('chatbot.index') }}" class="cart-submit-btn" style="padding: 8px 16px; width: auto; margin: 0; font-size: 0.875rem; background: #059669; color: #fff; text-decoration: none; border: 1px solid rgba(5, 150, 105, 0.5);">Trò chuyện mới</a>
				</div>

				{{-- Khung tin nhắn --}}
				<div class="chat-window" style="max-width: 100%; box-shadow: none;">
					<div class="chat-thread" style="max-height: 60vh;">
						@forelse($session->tinNhanChat as $message)
							<div class="chat-message {{ $message->nguoi_gui === 'người dùng' ? 'user' : 'bot' }}">
								{{ $message->noi_dung }}
								<div style="margin-top: 6px; font-size: 0.7rem; opacity: 0.6;">{{ $message->created_at?->format('H:i') }}</div>
							</div>
						@empty
							<div class="chat-message bot">Cuộc trò chuyện này chưa có tin nhắn nào.</div>
						@endforelse
					</div>
				</div>
			</div>
		</div>
	</main>
@endsection
