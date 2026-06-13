@extends('customer.layout.app')

@section('title', 'Lịch sử trò chuyện - XM Coffee')
@section('meta_description', 'Xem lại các cuộc trò chuyện của bạn với trợ lý XM Coffee.')

@section('header_overlay', 'bg-black/30')
@section('body_class', 'cart-page')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/cart.css') }}">
@endpush

@section('content')
	<main class="cart-main">
		<div class="cart-container">
			<div class="cart-orders-today" style="margin-top: 12px; margin-bottom: 40px; background: rgba(30, 17, 6, 0.5); border-radius: 24px; padding: 28px 24px; border: 1px solid rgba(255, 255, 255, 0.12); backdrop-filter: blur(14px);">

				{{-- Tiêu đề --}}
				<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
					<div style="flex: 1; min-width: 100px;"></div>
					<div style="text-align: center; flex: 2; min-width: 250px;">
						<h2 style="font-family: 'Playfair Display', serif; font-size: 24px; font-weight: 700; color: #fff; margin: 0 0 4px;">Lịch sử trò chuyện</h2>
						<p style="font-size: 0.8rem; color: rgba(255,255,255,0.55); margin: 0;">{{ $sessions->total() }} cuộc trò chuyện</p>
					</div>
					<div style="flex: 1; text-align: right; min-width: 100px;">
						<a href="{{ route('chatbot.index') }}" class="cart-submit-btn" style="padding: 8px 16px; width: auto; margin: 0; font-size: 0.875rem; background: #059669; color: #fff; text-decoration: none; border: 1px solid rgba(5, 150, 105, 0.5); display: inline-block;">Trò chuyện mới</a>
					</div>
				</div>

				@if($sessions->count() > 0)
					<div style="display: flex; flex-direction: column; gap: 12px;">
						@foreach($sessions as $session)
							@php
								$firstUserMsg = $session->tinNhanChat->firstWhere('nguoi_gui', 'người dùng');
								$preview = $firstUserMsg?->noi_dung ?? optional($session->tinNhanChat->first())->noi_dung ?? 'Cuộc trò chuyện';
								$lastMsg = $session->tinNhanChat->last();
								$lastTime = $lastMsg?->created_at ?? $session->created_at;
							@endphp
							<a href="{{ route('customer.chat_history.show', $session->id) }}"
								style="display: block; text-decoration: none; background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 14px; padding: 16px 18px; transition: background 0.2s, border-color 0.2s;"
								onmouseover="this.style.background='rgba(255,255,255,0.07)'; this.style.borderColor='rgba(240,221,184,0.3)';"
								onmouseout="this.style.background='rgba(255,255,255,0.03)'; this.style.borderColor='rgba(255,255,255,0.08)';">
								<div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px;">
									<div style="flex: 1; min-width: 0;">
										<div style="font-weight: 600; color: #F5EFE4; font-size: 0.95rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
											{{ \Illuminate\Support\Str::limit($preview, 80) }}
										</div>
										<div style="margin-top: 6px; font-size: 0.78rem; color: rgba(255,255,255,0.5);">
											{{ $session->tin_nhan_chat_count }} tin nhắn · {{ $lastTime?->format('d/m/Y H:i') }}
										</div>
									</div>
									<span style="color: #F0DDB8; font-size: 1.2rem; flex-shrink: 0;">&rsaquo;</span>
								</div>
							</a>
						@endforeach
					</div>

					<div style="margin-top: 16px;">
						{{ $sessions->links() }}
					</div>
				@else
					<div class="cart-empty" style="border: none; background: transparent; box-shadow: none; padding: 40px 20px;">
						<svg class="cart-empty-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
								d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.86 9.86 0 01-4-.84L3 20l1.16-3.48A7.92 7.92 0 013 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
						</svg>
						<h2>Chưa có cuộc trò chuyện nào</h2>
						<p>Hãy bắt đầu trò chuyện với trợ lý XM Coffee để nhận gợi ý món phù hợp nhé!</p>
						<a href="{{ route('chatbot.index') }}" class="cart-go-menu-btn" style="margin-top: 12px;">Trò chuyện ngay</a>
					</div>
				@endif
			</div>
		</div>
	</main>
@endsection
