<!-- ============ FOOTER ============ -->
<footer class="bg-[#30261C] text-[#F1F0EE] py-12 border-t border-[#8D5D5D]/30 relative z-10 w-full mt-auto">
    <div class="max-w-[1680px] mx-auto px-8 sm:px-12 lg:px-16 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12">
        
        <div>
            <h3 class="font-playfair text-2xl font-bold mb-6 text-[#F0DDB8]">XM Coffee</h3>
            <p class="opacity-80 font-outfit text-sm leading-relaxed mb-6">
                Nơi mang đến những tách cà phê đậm đà và không gian thư giãn tuyệt vời. Thưởng thức và cảm nhận sự khác biệt.
            </p>
        </div>
        
        <div>
            <h3 class="font-outfit font-bold text-lg mb-6 tracking-wide text-[#F0DDB8]">Liên kết nhanh</h3>
            <ul class="space-y-3 font-outfit text-sm opacity-80">
                <li><a href="{{ route('home') }}" class="hover:text-white transition">Trang chủ</a></li>
                <li><a href="{{ route('menu.index') }}" class="hover:text-white transition">Thực đơn</a></li>
                <li><a href="{{ route('home.about') }}" class="hover:text-white transition">Về chúng tôi</a></li>
                <li><a href="{{ route('home.contact') }}" class="hover:text-white transition">Liên hệ</a></li>
            </ul>
        </div>
        
        <div>
            <h3 class="font-outfit font-bold text-lg mb-6 tracking-wide text-[#F0DDB8]">Chính sách</h3>
            <ul class="space-y-3 font-outfit text-sm opacity-80">
                <li><a href="#" class="hover:text-white transition">Điều khoản sử dụng</a></li>
                <li><a href="#" class="hover:text-white transition">Chính sách bảo mật</a></li>
                <li><a href="#" class="hover:text-white transition">Chính sách hoàn tiền</a></li>
            </ul>
        </div>
        
        <div>
            <h3 class="font-outfit font-bold text-lg mb-6 tracking-wide text-[#F0DDB8]">Liên hệ</h3>
            <ul class="space-y-3 font-outfit text-sm opacity-80">
                <li class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-[#8D5D5D]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span>123 Đường Cà Phê, Quận 1, TP.HCM</span>
                </li>
                <li class="flex items-center gap-3 mt-2">
                    <svg class="w-5 h-5 text-[#8D5D5D]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                    </svg>
                    <span>0123 456 789</span>
                </li>
            </ul>
        </div>
        
    </div>
    
    <div class="max-w-[1680px] mx-auto px-8 sm:px-12 lg:px-16 mt-12 pt-8 border-t border-white/10 text-center opacity-60 font-outfit text-sm">
        &copy; {{ date('Y') }} XM Coffee. All rights reserved.
    </div>
</footer>
