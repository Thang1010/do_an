@extends('customer.layout.app')

@section('title', 'Chatbot - XM Coffee')
@section('meta_description', 'Trò chuyện cùng trợ lý XM Coffee để nhận gợi ý món phù hợp và đặt hàng ngay.')

@push('styles')
	<link rel="stylesheet" href="{{ asset('css/chatbot-page.css') }}">
@endpush

@section('content')
	<main id="chatbot-page" class="chatbot-page"
		data-chat-endpoint="{{ route('chatbot.message') }}"
		data-chat-suggest-endpoint="{{ route('chatbot.suggest') }}"
		data-cart-add-endpoint="{{ route('cart.add') }}">
		<section class="chatbot-shell">
			<div class="chat-window">
				<div class="chat-window-header">
					<div class="chat-window-title">
						<span>Trợ lý của XM Coffee</span>
						<strong>Gọi món cùng XM Coffee nhé </strong>
					</div>
					<div class="chat-status">Đang trực tuyến</div>
				</div>

				<div id="chat-messages" class="chat-thread">
					<div class="chat-message bot">Xin chào! Bạn cần hỗ trợ gì về menu, đặt bàn hoặc đơn hàng?</div>
				</div>

				<form id="chat-form" class="chat-input-bar" autocomplete="off">
					<input id="chat-input" type="text" placeholder="Nhập tin nhắn..." maxlength="2000" />
					<button type="submit">Gửi</button>
				</form>
			</div>

			<aside class="chat-suggestions">
				<div class="suggestions-header">
					<h2>Món gợi ý</h2>
					<p>Chọn món yêu thích và đặt hàng ngay từ khung này.</p>
				</div>
				<div id="chat-suggestions" class="chat-product-grid">
					<div class="chat-empty">Chưa có gợi ý nào. Hãy nhắn để mình bắt đầu nhé!</div>
				</div>
			</aside>
		</section>
	</main>
@endsection

@push('scripts')
	<script>
		(function () {
			var page = document.getElementById('chatbot-page');
			if (!page) return;

			var endpoint = page.getAttribute('data-chat-endpoint');
			var suggestEndpoint = page.getAttribute('data-chat-suggest-endpoint');
			var cartAddEndpoint = page.getAttribute('data-cart-add-endpoint');
			var csrf = document.querySelector('meta[name="csrf-token"]');
			var csrfToken = csrf ? csrf.getAttribute('content') : '';

			var form = document.getElementById('chat-form');
			var input = document.getElementById('chat-input');
			var messages = document.getElementById('chat-messages');
			var suggestions = document.getElementById('chat-suggestions');

			var chatHistory = [];
			var chatContext = {};
			var hasSuggested = false;

			function appendMessage(text, role, record) {
				var bubble = document.createElement('div');
				bubble.className = 'chat-message ' + (role === 'user' ? 'user' : 'bot');
				bubble.textContent = text;
				messages.appendChild(bubble);
				messages.scrollTop = messages.scrollHeight;
				if (record !== false) {
					chatHistory.push({ role: role === 'user' ? 'user' : 'assistant', content: text });
				}
				return bubble;
			}

			function setSuggestions(items) {
				suggestions.innerHTML = '';
				if (!items || items.length === 0) {
					var empty = document.createElement('div');
					empty.className = 'chat-empty';
					empty.textContent = 'Chưa có gợi ý nào. Hãy nhắn để mình bắt đầu nhé!';
					suggestions.appendChild(empty);
					return;
				}

				items.forEach(function (item) {
					var card = document.createElement('div');
					card.className = 'chat-product-card';

					var img = document.createElement('img');
					img.className = 'chat-product-img';
					img.alt = item.name || 'Sản phẩm';
					img.src = item.image_url || '';
					card.appendChild(img);

					var name = document.createElement('div');
					name.className = 'chat-product-name';
					name.textContent = item.name || 'Món mới';
					card.appendChild(name);

					var price = document.createElement('div');
					price.className = 'chat-product-price';
					price.textContent = item.price || 'Giá đang cập nhật';
					card.appendChild(price);

					var btn = document.createElement('button');
					btn.type = 'button';
					btn.className = 'chat-buy-btn';
					btn.textContent = 'Mua ngay';
					btn.setAttribute('data-product-id', item.id || '');
					card.appendChild(btn);

					suggestions.appendChild(card);
				});
			}

			function launchCartAnimation(imgEl, imgSrc) {
				var cartBtnDesktop = document.getElementById('cart-btn');
				var cartBtnMobile = document.getElementById('cart-btn-mobile');
				var targetCart = cartBtnDesktop;
				if (cartBtnMobile) {
					var mobileWrap = cartBtnMobile.closest('.lg\\:hidden') || cartBtnMobile.parentElement;
					if (mobileWrap && getComputedStyle(mobileWrap).display !== 'none') targetCart = cartBtnMobile;
				}
				if (!targetCart) return;

				var imgRect = imgEl ? imgEl.getBoundingClientRect() : { left: window.innerWidth / 2, top: window.innerHeight / 2, width: 60, height: 60 };
				var cartRect = targetCart.getBoundingClientRect();

				var wrapper = document.createElement('div');
				wrapper.style.cssText = 'position:fixed;left:' + imgRect.left + 'px;top:' + imgRect.top + 'px;width:' + imgRect.width + 'px;height:' + imgRect.height + 'px;z-index:9999;pointer-events:none;';

				var ghost = document.createElement('img');
				ghost.src = imgSrc;
				ghost.style.cssText = 'width:100%;height:100%;border-radius:50%;object-fit:cover;box-shadow:0 10px 25px rgba(0,0,0,.3);';
				wrapper.appendChild(ghost);
				document.body.appendChild(wrapper);

				var deltaX = (cartRect.left + cartRect.width / 2) - (imgRect.left + imgRect.width / 2);
				var deltaY = (cartRect.top + cartRect.height / 2) - (imgRect.top + imgRect.height / 2);
				var dur = 750;

				wrapper.animate([{ transform: 'translateX(0)' }, { transform: 'translateX(' + deltaX + 'px)' }],
					{ duration: dur, easing: 'linear', fill: 'forwards' });
				ghost.animate([
					{ transform: 'translateY(0) scale(1)', opacity: 0.95 },
					{ transform: 'translateY(' + (deltaY - 80) + 'px) scale(0.5)', opacity: 0.7, offset: 0.4 },
					{ transform: 'translateY(' + deltaY + 'px) scale(0.15)', opacity: 0 },
				], { duration: dur, easing: 'ease-in-out', fill: 'forwards' });

				setTimeout(function () {
					wrapper.remove();
					targetCart.classList.add('cart-bounce');
					setTimeout(function () { targetCart.classList.remove('cart-bounce'); }, 500);
				}, dur);
			}

			function addToCart(productId, button, imgEl, imgSrc) {
				if (!productId || !cartAddEndpoint) return;

				button.disabled = true;
				button.textContent = 'Đang thêm...';

				fetch(cartAddEndpoint, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'X-CSRF-TOKEN': csrfToken
					},
					body: JSON.stringify({ product_id: productId, qty: 1 })
				})
					.then(function (response) {
						return response.json().then(function (data) {
							return { ok: response.ok, data: data };
						});
					})
					.then(function (payload) {
						if (!payload.ok || !payload.data || !payload.data.success) {
							button.textContent = 'Thử lại';
							button.disabled = false;
							return;
						}

						launchCartAnimation(imgEl, imgSrc || (imgEl ? imgEl.src : ''));

						button.textContent = 'Đã thêm';
						setTimeout(function () {
							button.textContent = 'Mua ngay';
							button.disabled = false;
						}, 1200);

						if (window.updateCartBadge) {
							window.updateCartBadge(payload.data.cart_count || 0);
						}
					})
					.catch(function () {
						button.textContent = 'Thử lại';
						button.disabled = false;
					});
			}

			function requestSuggestions() {
				if (hasSuggested || !suggestEndpoint) {
					return;
				}
				hasSuggested = true;
				var typing = appendMessage('Đang lấy gợi ý từ menu...', 'bot', false);
				typing.classList.add('typing');

				fetch(suggestEndpoint, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'X-CSRF-TOKEN': csrfToken
					},
					body: JSON.stringify({})
				})
					.then(function (response) {
						return response.json().then(function (data) {
							return { ok: response.ok, data: data };
						});
					})
					.then(function (payload) {
						messages.removeChild(typing);
						if (!payload.ok) {
							appendMessage(payload.data && payload.data.error ? payload.data.error : 'Có lỗi khi lấy gợi ý.', 'bot');
							return;
						}
						chatContext = payload.data && payload.data.context ? payload.data.context : {};
						appendMessage(payload.data && payload.data.reply ? payload.data.reply : 'Mình đã sẵn sàng hỗ trợ!', 'bot');
						if (payload.data && payload.data.products) {
							setSuggestions(payload.data.products);
						}
					})
					.catch(function () {
						messages.removeChild(typing);
						appendMessage('Không thể lấy gợi ý lúc này. Bạn có thể cho mình biết món bạn thích không?', 'bot');
					});
			}

			form.addEventListener('submit', function (event) {
				event.preventDefault();
				var text = (input.value || '').trim();
				if (!text) return;
				appendMessage(text, 'user');
				input.value = '';

				var typing = appendMessage('Đang soạn trả lời...', 'bot', false);
				typing.classList.add('typing');

				fetch(endpoint, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'X-CSRF-TOKEN': csrfToken
					},
					body: JSON.stringify({
						message: text,
						history: chatHistory.slice(-8),
						context: chatContext
					})
				})
					.then(function (response) {
						return response.json().then(function (data) {
							return { ok: response.ok, data: data };
						});
					})
					.then(function (payload) {
						messages.removeChild(typing);
						if (!payload.ok) {
							appendMessage(payload.data && payload.data.error ? payload.data.error : 'Có lỗi xảy ra. Vui lòng thử lại.', 'bot');
							return;
						}

						if (payload.data && payload.data.context) {
							chatContext = payload.data.context;
						}

						appendMessage(payload.data && payload.data.reply ? payload.data.reply : 'Mình chưa hiểu ý bạn.', 'bot');

						if (payload.data && payload.data.products) {
							setSuggestions(payload.data.products);
						}
					})
					.catch(function () {
						messages.removeChild(typing);
						appendMessage('Không thể kết nối đến máy chủ. Vui lòng thử lại.', 'bot');
					});
			});

			suggestions.addEventListener('click', function (event) {
				var button = event.target.closest('.chat-buy-btn');
				if (!button) return;
				var productId = button.getAttribute('data-product-id');
				var card = button.closest('.chat-product-card');
				var imgEl = card ? card.querySelector('.chat-product-img') : null;
				var imgSrc = imgEl ? imgEl.src : '';
				addToCart(productId, button, imgEl, imgSrc);
			});

			requestSuggestions();
		})();
	</script>
@endpush
