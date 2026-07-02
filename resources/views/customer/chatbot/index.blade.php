@extends('customer.layout.app')

@section('title', 'Chatbot - XM Coffee')
@section('meta_description', 'Trò chuyện cùng trợ lý XM Coffee để nhận gợi ý món phù hợp và đặt hàng ngay.')

@push('styles')
	<link rel="stylesheet" href="{{ asset('css/chatbot-page.css') }}">
	<style>
		.chat-window { position: relative; }

		.chat-history-toggle {
			display: inline-flex;
			align-items: center;
			justify-content: center;
			width: 38px;
			height: 38px;
			border-radius: 50%;
			border: 1px solid rgba(255, 255, 255, 0.25);
			background: rgba(255, 255, 255, 0.08);
			color: var(--cream-100);
			cursor: pointer;
			transition: background 0.2s, border-color 0.2s;
			flex-shrink: 0;
		}
		.chat-history-toggle:hover { background: rgba(255, 255, 255, 0.18); border-color: rgba(255, 255, 255, 0.5); }

		.chat-history-overlay {
			position: absolute;
			inset: 0;
			background: rgba(20, 12, 6, 0.5);
			opacity: 0;
			visibility: hidden;
			transition: opacity 0.25s;
			z-index: 20;
		}
		.chat-history-overlay.open { opacity: 1; visibility: visible; }

		.chat-history-drawer {
			position: absolute;
			top: 0;
			bottom: 0;
			left: 0;
			width: min(320px, 82%);
			background: linear-gradient(160deg, #241509, #2f1d10);
			border-right: 1px solid rgba(228, 203, 162, 0.18);
			box-shadow: 12px 0 40px rgba(0, 0, 0, 0.4);
			transform: translateX(-100%);
			transition: transform 0.28s ease;
			z-index: 21;
			display: flex;
			flex-direction: column;
			padding: 16px 14px;
		}
		.chat-history-drawer.open { transform: translateX(0); }

		.chat-history-drawer-head {
			display: flex;
			align-items: center;
			justify-content: space-between;
			color: var(--gold-300);
			font-family: 'Outfit', sans-serif;
			font-weight: 600;
			font-size: 0.95rem;
			padding: 4px 6px 12px;
		}
		.chat-history-drawer-head button {
			background: none;
			border: none;
			color: rgba(248, 244, 238, 0.7);
			font-size: 1rem;
			cursor: pointer;
			line-height: 1;
		}
		.chat-history-drawer-head button:hover { color: #fff; }

		.chat-new-btn {
			display: flex;
			align-items: center;
			justify-content: center;
			gap: 8px;
			width: 100%;
			padding: 10px;
			margin-bottom: 12px;
			border-radius: 10px;
			border: 1px dashed rgba(228, 203, 162, 0.4);
			background: rgba(255, 255, 255, 0.04);
			color: var(--cream-100);
			font-family: 'Outfit', sans-serif;
			font-size: 0.85rem;
			font-weight: 600;
			cursor: pointer;
			transition: background 0.2s;
		}
		.chat-new-btn:hover { background: rgba(255, 255, 255, 0.1); }

		.chat-history-list {
			flex: 1;
			overflow-y: auto;
			display: flex;
			flex-direction: column;
			gap: 6px;
			padding-right: 2px;
		}

		.chat-history-item {
			text-align: left;
			width: 100%;
			background: rgba(255, 255, 255, 0.03);
			border: 1px solid transparent;
			border-radius: 10px;
			padding: 10px 12px;
			color: var(--cream-100);
			cursor: pointer;
			font-family: 'Outfit', sans-serif;
			transition: background 0.2s, border-color 0.2s;
		}
		.chat-history-item:hover { background: rgba(255, 255, 255, 0.08); }
		.chat-history-item.active { background: rgba(228, 203, 162, 0.16); border-color: rgba(228, 203, 162, 0.5); }
		.chat-history-item .preview {
			font-size: 0.85rem;
			font-weight: 500;
			line-height: 1.35;
			display: -webkit-box;
			-webkit-line-clamp: 2;
			-webkit-box-orient: vertical;
			overflow: hidden;
		}
		.chat-history-item .meta {
			margin-top: 5px;
			font-size: 0.7rem;
			color: rgba(248, 244, 238, 0.5);
		}

		.chat-history-empty {
			color: rgba(248, 244, 238, 0.55);
			font-family: 'Outfit', sans-serif;
			font-size: 0.85rem;
			text-align: center;
			padding: 24px 8px;
		}

		/* Nút Voice Chatbot */
		.chat-voice-btn {
			background: none;
			border: none;
			color: rgba(255, 255, 255, 0.6);
			cursor: pointer;
			padding: 8px;
			margin-right: 4px;
			border-radius: 50%;
			transition: all 0.2s;
			display: flex;
			align-items: center;
			justify-content: center;
		}
		.chat-voice-btn:hover { color: #fff; background: rgba(255,255,255,0.1); }
		.chat-voice-btn.recording { color: #ff4a4a; animation: pulse-mic 1.5s infinite; }
		@keyframes pulse-mic {
			0% { transform: scale(1); }
			50% { transform: scale(1.15); color: #ff0000; }
			100% { transform: scale(1); }
		}
	</style>
@endpush

@section('content')
	<main id="chatbot-page" class="chatbot-page"
		data-chat-endpoint="{{ route('chatbot.message') }}"
		data-chat-suggest-endpoint="{{ route('chatbot.suggest') }}"
		data-cart-add-endpoint="{{ route('cart.add') }}"
		data-chat-sessions-endpoint="{{ route('chatbot.sessions') }}"
		data-chat-reset-endpoint="{{ route('chatbot.sessions.reset') }}"
		data-authenticated="{{ auth()->check() ? '1' : '0' }}">
		<section class="chatbot-shell">
			<div class="chat-window">
				<div class="chat-window-header" style="position: relative; display: flex; align-items: center; justify-content: center;">
					@auth
						<button type="button" id="chat-history-toggle" class="chat-history-toggle" aria-label="Menu" title="Menu" style="position: absolute; left: 16px;">
							<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
								<line x1="3" y1="12" x2="21" y2="12"></line>
								<line x1="3" y1="6" x2="21" y2="6"></line>
								<line x1="3" y1="18" x2="21" y2="18"></line>
							</svg>
						</button>
					@endauth
					<div class="chat-window-title" style="text-align: center; display: flex; flex-direction: column; align-items: center;">
						<span>Trợ lý của XM Coffee</span>
						<strong>Gọi món cùng XM Coffee nhé </strong>
					</div>
					<div class="chat-status" style="position: absolute; right: 16px;">Đang trực tuyến</div>
				</div>

				@auth
					<div id="chat-history-overlay" class="chat-history-overlay"></div>
					<aside id="chat-history-drawer" class="chat-history-drawer" aria-hidden="true">
						<div class="chat-history-drawer-head">
							<span>Lịch sử trò chuyện</span>
							<button type="button" id="chat-history-close" aria-label="Đóng">&#x2715;</button>
						</div>
						<button type="button" id="chat-new-btn" class="chat-new-btn">
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
							Cuộc trò chuyện mới
						</button>
						<div id="chat-history-list" class="chat-history-list">
							<div class="chat-history-empty">Đang tải...</div>
						</div>
					</aside>
				@endauth

				<div id="chat-messages" class="chat-thread">
					<div class="chat-message bot">Xin chào! Bạn cần hỗ trợ gì về menu, đặt bàn hoặc đơn hàng?</div>
				</div>

				<form id="chat-form" class="chat-input-bar" autocomplete="off">
					<button type="button" id="chatbot-voice-btn" class="chat-voice-btn" aria-label="Ghi âm giọng nói">
						<svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
						</svg>
					</button>
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

			function escapeHtml(s) {
				return String(s)
					.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
					.replace(/"/g, '&quot;').replace(/'/g, '&#39;');
			}

			// Escape an toàn rồi biến link Markdown [chữ](url) và URL trần thành thẻ <a> bấm được.
			function linkifyBotText(rawText) {
				var safe = escapeHtml(rawText);
				safe = safe.replace(/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/g, function (m, label, url) {
					return '<a href="' + url + '" target="_blank" rel="noopener noreferrer">' + label + '</a>';
				});
				safe = safe.replace(/(^|[\s(])(https?:\/\/[^\s<]+)/g, function (m, pre, url) {
					return pre + '<a href="' + url + '" target="_blank" rel="noopener noreferrer">' + url + '</a>';
				});
				// Markdown nhan manh: thu tu 3 sao -> 2 sao -> 1 sao (tranh nuot nhau).
				safe = safe.replace(/\*\*\*([^*]+)\*\*\*/g, '<strong><em>$1</em></strong>');
				safe = safe.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
				safe = safe.replace(/\*([^*\n]+)\*/g, '<em>$1</em>');
				return safe;
			}

			function appendMessage(text, role, record) {
				var bubble = document.createElement('div');
				bubble.className = 'chat-message ' + (role === 'user' ? 'user' : 'bot');
				if (role === 'user') {
					bubble.textContent = text;
				} else {
					bubble.innerHTML = linkifyBotText(text);
				}
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
					btn.setAttribute('data-sizes', JSON.stringify(item.sizes || []));
					btn.setAttribute('data-nhiet-do', item.nhiet_do || '');
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

			function addToCart(productId, sizeId, button, imgEl, imgSrc) {
				if (!productId || !cartAddEndpoint) return;

				button.disabled = true;
				button.textContent = 'Đang thêm...';

				var body = { product_id: productId, qty: 1 };
				if (sizeId) body.size_id = sizeId;

				fetch(cartAddEndpoint, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'X-CSRF-TOKEN': csrfToken
					},
					body: JSON.stringify(body)
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
				
				// Helper gửi tin nhắn
				sendMessageToBot(text);
			});

			function sendMessageToBot(text, systemInjection = '') {
				appendMessage(text, 'user', false); // Chỉ hiển thị UI, khoan lưu vào mảng
				input.value = '';

				// Thêm vào mảng History với System Injection (nếu có) để AI đọc được nhưng khách không thấy
				chatHistory.push({ role: 'user', content: text + (systemInjection ? "\n" + systemInjection : "") });

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

						// Bot vừa chỉnh sửa giỏ hàng (ghi chú/nhiệt độ) → cập nhật badge giỏ.
						if (payload.data && payload.data.cart_updated && window.updateCartBadge) {
							window.updateCartBadge(payload.data.cart_count || 0);
						}
					})
					.catch(function () {
						messages.removeChild(typing);
						appendMessage('Không thể kết nối đến máy chủ. Vui lòng thử lại.', 'bot');
					});
			}

			// ===== VOICE ORDER TRONG CHATBOT =====
			var voiceBtn = document.getElementById('chatbot-voice-btn');
			var mediaRecorder;
			var audioChunks = [];
			var isRecording = false;

			if (voiceBtn) {
				voiceBtn.addEventListener('click', async function() {
					if (isRecording) {
						// Tắt ghi âm
						mediaRecorder.stop();
						voiceBtn.classList.remove('recording');
						isRecording = false;
						input.value = 'Đang phân tích giọng nói...';
						input.disabled = true;
						return;
					}

					try {
						// Bật DSP của trình duyệt (khử ồn/vọng + tự động chỉnh gain) → thu rõ hơn hẳn.
						const stream = await navigator.mediaDevices.getUserMedia({
							audio: {
								channelCount: 1,
								echoCancellation: true,
								noiseSuppression: true,
								autoGainControl: true,
								sampleRate: 16000
							}
						});
						mediaRecorder = new MediaRecorder(stream);
						audioChunks = [];

						mediaRecorder.addEventListener('dataavailable', event => {
							audioChunks.push(event.data);
						});

						mediaRecorder.addEventListener('stop', async () => {
							stream.getTracks().forEach(track => track.stop());
							// MediaRecorder xuất WebM/Opus mà API nhận dạng (Whisper qua Hugging Face)
							// KHÔNG giải mã được (soundfile chỉ đọc wav/flac/mp3) → nghe thành im lặng
							// và trả về "you". Vì vậy chuyển sang WAV PCM 16kHz mono trước khi gửi.
							try {
								const rawBlob = new Blob(audioChunks, { type: mediaRecorder.mimeType || 'audio/webm' });
								const wavBlob = await blobToWav(rawBlob);
								sendVoiceToChat(wavBlob);
							} catch (e) {
								input.value = '';
								input.disabled = false;
								appendMessage('Xin lỗi, không xử lý được âm thanh. Vui lòng thử lại.', 'bot');
							}
						});

						mediaRecorder.start();
						isRecording = true;
						voiceBtn.classList.add('recording');
						input.value = 'Đang ghi âm (Bấm lại Micro để Dừng)...';
						input.disabled = true;
					} catch(err) {
						alert('Vui lòng cấp quyền Micro để sử dụng tính năng Voice.');
					}
				});
			}

			// Giải mã audio ghi được (WebM/Opus...) rồi encode lại thành WAV PCM 16-bit,
			// 16kHz, mono — định dạng mà Whisper (Hugging Face) đọc ổn định.
			async function blobToWav(blob) {
				const arrayBuffer = await blob.arrayBuffer();
				const AudioCtx = window.AudioContext || window.webkitAudioContext;
				const audioCtx = new AudioCtx();
				const decoded = await audioCtx.decodeAudioData(arrayBuffer);
				audioCtx.close();

				const targetRate = 16000;
				const numCh = decoded.numberOfChannels;
				const len = decoded.length;

				// Downmix về mono
				const mono = new Float32Array(len);
				for (let ch = 0; ch < numCh; ch++) {
					const data = decoded.getChannelData(ch);
					for (let i = 0; i < len; i++) mono[i] += data[i] / numCh;
				}

				// Resample tuyến tính về 16kHz
				const ratio = decoded.sampleRate / targetRate;
				const outLen = Math.max(1, Math.round(len / ratio));
				const out = new Float32Array(outLen);
				for (let i = 0; i < outLen; i++) {
					const idx = i * ratio;
					const i0 = Math.floor(idx);
					const i1 = Math.min(i0 + 1, len - 1);
					const frac = idx - i0;
					out[i] = mono[i0] * (1 - frac) + mono[i1] * frac;
				}

				// Chuẩn hóa âm lượng (peak normalization): khuếch đại giọng thu nhỏ lên gần mức tối đa
				// để Whisper nghe rõ, nhưng giới hạn hệ số khuếch đại để không thổi phồng tạp âm khi im lặng.
				let peak = 0;
				for (let i = 0; i < outLen; i++) {
					const a = Math.abs(out[i]);
					if (a > peak) peak = a;
				}
				if (peak > 0.001) {
					const gain = Math.min(0.97 / peak, 8); // trần gain x8 tránh khuếch đại nhiễu
					for (let i = 0; i < outLen; i++) out[i] *= gain;
				}

				// Encode WAV 16-bit PCM mono
				const buffer = new ArrayBuffer(44 + outLen * 2);
				const view = new DataView(buffer);
				const writeStr = (off, s) => { for (let i = 0; i < s.length; i++) view.setUint8(off + i, s.charCodeAt(i)); };
				writeStr(0, 'RIFF');
				view.setUint32(4, 36 + outLen * 2, true);
				writeStr(8, 'WAVE');
				writeStr(12, 'fmt ');
				view.setUint32(16, 16, true);
				view.setUint16(20, 1, true);           // PCM
				view.setUint16(22, 1, true);           // mono
				view.setUint32(24, targetRate, true);
				view.setUint32(28, targetRate * 2, true); // byte rate = rate * blockAlign
				view.setUint16(32, 2, true);           // block align = channels * bytesPerSample
				view.setUint16(34, 16, true);          // bits per sample
				writeStr(36, 'data');
				view.setUint32(40, outLen * 2, true);
				let off = 44;
				for (let i = 0; i < outLen; i++) {
					let s = Math.max(-1, Math.min(1, out[i]));
					view.setInt16(off, s < 0 ? s * 0x8000 : s * 0x7FFF, true);
					off += 2;
				}
				return new Blob([view], { type: 'audio/wav' });
			}

			function sendVoiceToChat(audioBlob) {
				var formData = new FormData();
				formData.append('audio', audioBlob, 'chat_audio.wav');

				fetch('{{ route("cart.voice_order") }}', {
					method: 'POST',
					headers: { 'X-CSRF-TOKEN': csrfToken },
					body: formData
				})
				.then(res => res.json())
				.then(data => {
					input.value = '';
					input.disabled = false;

					if (data.text) {
						var sysInj = "";
						if (data.success) {
							// Cập nhật giỏ hàng trên UI
							if (data.cart_count && window.updateCartBadge) {
								updateCartBadge(data.cart_count);
							}
							// Bơm hướng dẫn ngầm cho OpenAI
							sysInj = "[HỆ THỐNG: Hệ thống đã TỰ ĐỘNG THÊM món khách yêu cầu vào giỏ hàng. BOT HÃY phản hồi xác nhận ĐÃ THÊM VÀO GIỎ và hỏi khách có muốn dặn dò gì thêm về món đó không (ít đá, ít đường...)? Trả lời theo ĐÚNG ngôn ngữ của khách (Việt hoặc Anh). | Reply in the customer's language.]";
						} else {
							sysInj = "[HỆ THỐNG: Không tìm thấy món khách đọc trong Menu, BOT hãy xin lỗi và gợi ý menu hiện tại. Trả lời theo ĐÚNG ngôn ngữ của khách (Việt hoặc Anh). | Reply in the customer's language.]";
						}
						sendMessageToBot(data.text, sysInj);
					} else {
						appendMessage("Xin lỗi, AI không nghe rõ bạn nói gì. Vui lòng thử lại.", 'bot');
					}
				})
				.catch(err => {
					input.value = '';
					input.disabled = false;
					appendMessage("Lỗi xử lý giọng nói.", 'bot');
				});
			}

			suggestions.addEventListener('click', function (event) {
				var button = event.target.closest('.chat-buy-btn');
				if (!button) return;
				var productId = button.getAttribute('data-product-id');
				var card = button.closest('.chat-product-card');
				var imgEl = card ? card.querySelector('.chat-product-img') : null;
				var imgSrc = imgEl ? imgEl.src : '';

				var sizes = [];
				try { sizes = JSON.parse(button.getAttribute('data-sizes') || '[]'); } catch (e) {}
				
				var productName = card ? (card.querySelector('.chat-product-name') ? card.querySelector('.chat-product-name').textContent : '') : '';
				var nhietDo = button.getAttribute('data-nhiet-do') || '';
				
				var suggestedNote = '';
				if (chatContext && chatContext.chatbot_notes && productName) {
					var lowerName = productName.toLowerCase().trim();
					if (chatContext.chatbot_notes[lowerName]) {
						suggestedNote = chatContext.chatbot_notes[lowerName];
					}
				}

				if (typeof window.showGlobalSizeModal === 'function') {
					window.showGlobalSizeModal(productId, productName, imgSrc, cartAddEndpoint, sizes, imgEl, nhietDo, suggestedNote);
				}
			});

			// ===== Lịch sử trò chuyện (kiểu ChatGPT) =====
			var sessionsEndpoint = page.getAttribute('data-chat-sessions-endpoint');
			var resetEndpoint = page.getAttribute('data-chat-reset-endpoint');

			var historyToggle = document.getElementById('chat-history-toggle');
			var historyDrawer = document.getElementById('chat-history-drawer');
			var historyOverlay = document.getElementById('chat-history-overlay');
			var historyClose = document.getElementById('chat-history-close');
			var historyList = document.getElementById('chat-history-list');
			var newChatBtn = document.getElementById('chat-new-btn');

			function openHistory() {
				if (!historyDrawer) return;
				historyDrawer.classList.add('open');
				historyOverlay.classList.add('open');
				historyDrawer.setAttribute('aria-hidden', 'false');
				loadSessions();
			}

			function closeHistory() {
				if (!historyDrawer) return;
				historyDrawer.classList.remove('open');
				historyOverlay.classList.remove('open');
				historyDrawer.setAttribute('aria-hidden', 'true');
			}

			function loadSessions() {
				if (!historyList || !sessionsEndpoint) return;
				historyList.innerHTML = '<div class="chat-history-empty">Đang tải...</div>';
				fetch(sessionsEndpoint, { headers: { 'Accept': 'application/json' } })
					.then(function (r) { return r.json(); })
					.then(function (data) {
						var sessions = (data && data.sessions) || [];
						if (!sessions.length) {
							historyList.innerHTML = '<div class="chat-history-empty">Chưa có cuộc trò chuyện nào.</div>';
							return;
						}
						historyList.innerHTML = '';
						sessions.forEach(function (s) {
							var item = document.createElement('button');
							item.type = 'button';
							item.className = 'chat-history-item' + (s.active ? ' active' : '');

							var preview = document.createElement('div');
							preview.className = 'preview';
							preview.textContent = s.preview || 'Cuộc trò chuyện';
							item.appendChild(preview);

							var meta = document.createElement('div');
							meta.className = 'meta';
							meta.textContent = (s.count || 0) + ' tin nhắn · ' + (s.time || '');
							item.appendChild(meta);

							item.addEventListener('click', function () { loadSession(s.id); });
							historyList.appendChild(item);
						});
					})
					.catch(function () {
						historyList.innerHTML = '<div class="chat-history-empty">Không tải được lịch sử.</div>';
					});
			}

			function loadSession(id) {
				fetch(sessionsEndpoint + '/' + id, { headers: { 'Accept': 'application/json' } })
					.then(function (r) { return r.json().then(function (d) { return { ok: r.ok, data: d }; }); })
					.then(function (payload) {
						if (!payload.ok) return;
						var msgs = (payload.data && payload.data.messages) || [];
						messages.innerHTML = '';
						chatHistory = [];
						chatContext = {};
						hasSuggested = true; // mở lại cuộc cũ thì không tự gợi ý đè lên
						msgs.forEach(function (m) {
							appendMessage(m.content, m.role === 'user' ? 'user' : 'bot');
						});
						closeHistory();
					})
					.catch(function () {});
			}

			function startNewChat() {
				if (!resetEndpoint) return;
				fetch(resetEndpoint, {
					method: 'POST',
					headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
					body: JSON.stringify({})
				}).then(function () {
					messages.innerHTML = '';
					chatHistory = [];
					chatContext = {};
					hasSuggested = false;
					appendMessage('Xin chào! Bạn cần hỗ trợ gì về menu, đặt bàn hoặc đơn hàng?', 'bot', false);
					closeHistory();
					requestSuggestions();
				}).catch(function () {});
			}

			if (historyToggle) historyToggle.addEventListener('click', openHistory);
			if (historyClose) historyClose.addEventListener('click', closeHistory);
			if (historyOverlay) historyOverlay.addEventListener('click', closeHistory);
			if (newChatBtn) newChatBtn.addEventListener('click', startNewChat);

			requestSuggestions();
		})();
	</script>
@endpush
