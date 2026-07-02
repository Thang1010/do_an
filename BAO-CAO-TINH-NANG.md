# BÁO CÁO CÁC TÍNH NĂNG CỐT LÕI CÓ TÍCH HỢP API / THƯ VIỆN — HỆ THỐNG QUẢN LÝ QUÁN CAFE/TRÀ

> Tài liệu này bám sát 100% mã nguồn thực tế của dự án (Laravel). Chỉ liệt kê các tính năng **có sử dụng API bên ngoài hoặc thư viện**. Các chức năng thuần CRUD/SQL/framework (không tích hợp thư viện hay API) đã được lược bỏ.

---

## CÁC TÍNH NĂNG CỐT LÕI

---

### Chức năng 1: Trợ lý ảo AI & Tự động tinh chỉnh giỏ hàng bằng ngôn ngữ tự nhiên
**API/Thư viện dùng:** OpenAI API (`gpt-4o-mini`) · Hugging Face Inference API (`whisper-large-v3-turbo`) · OpenWeatherMap API

#### Nơi xử lý trong code
- **Controller:** `app/Http/Controllers/Customer/ChatbotController.php` (method `message`, `suggest`).
- **AI văn bản (chat):** gọi trực tiếp **OpenAI API** — `https://api.openai.com/v1/chat/completions`, model **`gpt-4o-mini`** (key `OPENAI_API_KEY`).
- **AI giọng nói (voice):** `app/Services/HuggingFaceService.php` — gọi **Hugging Face Inference API** (`whisper-large-v3-turbo`, key `HUGGINGFACE_TOKEN`).
- **Model liên quan:** `PhienChat`, `TinNhanChat`, `SanPham`, `DonHang`, `CuaHang`.

> **Lưu ý kỹ thuật:** Hệ thống dùng **2 nhà cung cấp AI khác nhau** — trả lời văn bản dùng **OpenAI**, nhận dạng giọng nói dùng **Hugging Face** (host model Whisper của OpenAI).

#### Luồng xử lý chi tiết
1. **Thu thập ngữ cảnh cá nhân hóa** — khi khách nhắn tin, controller truy vấn và gộp vào `systemPrompt`:
   - Lịch sử đặt hàng gần nhất — `getPastOrdersText()`.
   - Sản phẩm khách đã yêu thích — `getFavoriteItems()`.
   - Đánh giá sao trước đây của khách (lọc `so_sao >= 4`) — `getPastReviewsText()`.
   - **Thời tiết thực tế** — `getWeatherContext()` gọi **OpenWeatherMap API** (`api.openweathermap.org/data/2.5/weather`, key `OPENWEATHERMAP_API_KEY`, có cache).
   - Thông tin cửa hàng — `CuaHang::first()` trong `buildStoreContext()`.
   - Toàn bộ được đưa vào tin nhắn `role: system` rồi gửi lên OpenAI qua `callOpenAi()`.

2. **Tự động tinh chỉnh giỏ hàng bằng AI (điểm nhấn kỹ thuật):**
   - `looksLikeCartEdit()` quét từ khóa nhu cầu sửa giỏ (nóng, lạnh, đá, ít đường, thêm sữa, ghi chú...).
   - Nếu phát hiện → gọi `applyCartEditsViaAi()` chạy OpenAI ở **JSON Mode** (`response_format: {type: json_object}`) qua `callOpenAiJson()`.
   - AI phân tích giỏ hàng hiện tại (lưu trong Session) và trả về danh sách `edits`, hệ thống áp dụng thay đổi thuộc tính từng món: **số lượng (qty), nhiệt độ (nhiet_do), ghi chú (ghi_chu)**, rồi **ghi đè lại Session Cart** — khách không cần thao tác tay.

3. **Gọi món bằng giọng nói (Voice Ordering):**
   - Khách bấm nút Micro trên giao diện Menu, trình duyệt ghi âm và gửi file audio (`audio/webm`) qua AJAX.
   - `HuggingFaceService::speechToText()` gửi raw audio lên Hugging Face model **whisper-large-v3-turbo** (đa ngôn ngữ, hỗ trợ tiếng Việt, timeout 60s) → trả về văn bản → đưa vào luồng xử lý giỏ hàng của chatbot.

---

### Chức năng 2: Điểm danh bảo mật bằng QR có chữ ký + xác thực vị trí GPS
**API/Thư viện dùng:** `simplesoftwareio/simple-qrcode` (sinh QR) · Nominatim/OpenStreetMap API (geocoding) · HTML5 Geolocation Web API

#### Nơi xử lý trong code
- **Service:** `app/Services/AttendanceService.php` (Haversine, kiểm tra bán kính, kiểm tra khung giờ).
- **Controller:** `app/Http/Controllers/Staff/ShiftController.php` (nhân viên check-in/out) và `app/Http/Controllers/Manager/ShiftController.php` (quản lý sinh mã QR).
- **Geocoding:** `GeocodingService` — gọi **Nominatim (OpenStreetMap) API** chuyển địa chỉ quán → tọa độ, có cache.
- **Sinh QR:** thư viện **`simple-qrcode`** (`QrCode::format('svg')->generate(...)`).
- **Bảng/Model:** `cham_cong` — cột thực tế: `nguoi_dung_id`, `ca_lam_viec_id`, `cham_cong_vao`, `cham_cong_ra`, `ghi_chu`.

#### Luồng xử lý chi tiết
1. **Sinh QR có chữ ký (chống chụp ảnh mang về nhà quét):**
   - Quản lý sinh QR bằng **Laravel Signed URL** — `URL::temporarySignedRoute('shifts.checkin.scan', now()->addHours(12), ...)`, render thành ảnh QR bằng thư viện `simple-qrcode`.
   - Chữ ký là **HMAC-SHA256** gắn kèm trong URL (không nằm trong ảnh QR), hết hạn sau **12 giờ**.
   - Có thêm **TTL thứ 2 = 5 phút** cho thao tác submit (`config('attendance.submit_ttl_minutes')`).
   - Xác thực chữ ký khi quét: `$request->hasValidRelativeSignature()`.

2. **Quét mã & lấy vị trí:** Giao diện gọi **`navigator.geolocation.getCurrentPosition`** (HTML5 Geolocation Web API, `enableHighAccuracy: true`) lấy `latitude`/`longitude` thực tế của thiết bị, gửi kèm lượt chấm công.

3. **Xác thực vị trí (GPS geofencing):**
   - Tọa độ chuẩn của quán lấy từ **`cua_hang.dia_chi`** rồi geocode qua Nominatim (kết quả cache).
   - `AttendanceService::distanceMeters()` dùng **công thức Haversine** (bán kính Trái Đất 6.371.000 m) tính khoảng cách 2 điểm.
   - Nếu khoảng cách **> bán kính cho phép (mặc định 200 m**, cấu hình qua env `ATTENDANCE_GEO_RADIUS`) → **từ chối** với thông báo: *"Bạn đang ở cách quán khoảng X m (giới hạn Y m)..."*

   > **Lưu ý:** GPS chỉ dùng để **xác thực tại thời điểm chấm công**, **không lưu tọa độ vào bảng `cham_cong`**.

4. **Kiểm tra khung giờ:** So thời gian quét với giờ ca (`ca_lam_viec.gio_bat_dau/gio_ket_thuc`), `buildTimeDeviationNote()` sinh ghi chú **đi muộn/về sớm** lưu vào cột `ghi_chu`.

---

### Chức năng 3: Quản lý kho nguyên liệu thông minh, Cảnh báo Email tự động & Xuất/Nhập Excel
**API/Thư viện dùng:** `phpoffice/phpspreadsheet` (xuất/nhập Excel) · Laravel Mail + Queue (gửi email)

#### Nơi xử lý trong code
- **Model:** `app/Models/NguyenLieu.php`, `app/Models/LichSuKho.php`, `app/Models/CongThucSanPham.php`.
- **Controller:** `app/Http/Controllers/Manager/IngredientController.php`, `InventoryController.php`.
- **Observer:** `app/Observers/LichSuKhoObserver.php` (đăng ký trong `AppServiceProvider`).
- **Mail:** `app/Mail/LowStockMail.php` (implements `ShouldQueue`).
- **Thư viện Excel:** **`phpoffice/phpspreadsheet`**.

#### Luồng xử lý chi tiết
1. **Tồn kho được tính động (không lưu cột số lượng tĩnh):**
   - Không có cột `so_luong_ton`. Tồn kho tính từ bảng **`lich_su_kho`**: `SUM(nhập) − SUM(xuất) ± SUM(điều chỉnh)`.
   - **Phân loại 3 cấp theo số cốc pha được** = `FLOOR(tồn kho / mức tiêu hao lớn nhất theo công thức)`: `<= 0` cốc → **Hết hàng**; `1–20` cốc → **Sắp hết**; `> 20` cốc → **Đủ hàng**.

2. **Tự động gửi mail cảnh báo (Observer Pattern):**
   - Observer đặt trên model **`LichSuKho`** (sự kiện `created`), chỉ kích hoạt khi `loai_giao_dich = 'xuất kho'`.
   - Observer **so sánh trạng thái TRƯỚC và SAU** giao dịch; **chỉ gửi mail khi chuyển từ "Đủ hàng" → "Sắp hết"/"Hết hàng"**.
   - Gửi `LowStockMail` **qua Queue** tới **tất cả Quản lý + Chủ cửa hàng** đang hoạt động.

3. **Nhập kho & xuất Excel:**
   - Nhập kho có validate; mỗi lần nhập tạo bản ghi `LichSuKho` (`loai_giao_dich = 'nhập kho'`) và bản ghi chi phí `ChiTieu` liên kết `lich_su_kho_id`.
   - **Xuất Excel** (tồn kho, lịch sử nhập/xuất) dùng **PhpSpreadsheet**.

---

### Chức năng 4: Sinh mã QR & Gọi món tại bàn
**API/Thư viện dùng:** `simplesoftwareio/simple-qrcode` (sinh QR cho từng bàn)

#### Nơi xử lý trong code
- **Sinh QR bàn:** `app/Http/Controllers/Manager/TableController.php` + view `resources/views/manager/tables/qr-print.blade.php` — dùng thư viện **`simple-qrcode`**.
- **Quét & gọi món:** `app/Http/Controllers/Customer/HomeController.php` (`orderTable()`), `Customer/CartController.php`.
- **Model:** `BanAn`, `DonHang`, `ChiTietDonHang`.

#### Luồng xử lý chi tiết
1. Quản lý sinh **mã QR cho từng bàn** bằng thư viện `simple-qrcode`, in ra để đặt tại bàn. QR trỏ tới route dạng **`/order-table/{table}`** (`{table}` là **ID bàn**, route parameter — không phải query string `?table=5`).
2. Khách quét QR → `HomeController::orderTable()` lưu `session(['qr_ban_an_id' => $banAn->id])`.
3. Khi checkout, `CartController` tự gán bàn vào đơn (`ban_an_id`), giúp hiển thị đúng vị trí bàn cho nhân viên phục vụ.

---

### Chức năng 5: Thanh toán QR online qua cổng PayOS
**API/Thư viện dùng:** `payos/payos` (cổng thanh toán) · `simplesoftwareio/simple-qrcode`

#### Nơi xử lý trong code
- **Service:** `app/Services/PaymentService.php`, `app/Services/OrderInventoryService.php`.
- **Model:** `ThanhToan`, `DonHang`.
- **Thư viện:** **`payos/payos`**, cấu hình `config('services.payos')` (`PAYOS_CLIENT_ID / API_KEY / CHECKSUM_KEY`).

#### Luồng xử lý chi tiết
1. Hỗ trợ 2 phương thức: **tiền mặt** và **chuyển khoản QR** qua **PayOS**; nội dung chuyển khoản tự sinh (dạng `TT DON{id}`).
2. Đơn của khách **thanh toán trước**; PayOS trả về mã QR để khách quét.
3. Khi thanh toán thành công (webhook PayOS) → cập nhật `trang_thai_thanh_toan = 'đã thanh toán'` → **tự động xuất kho nguyên liệu** theo công thức món (`OrderInventoryService`).
4. Gửi thông báo real-time cho nhân viên/quản lý khi có thanh toán thành công / đơn mới.

---

### Chức năng 6: Đánh giá sản phẩm & Phân tích cảm xúc bằng AI
**API/Thư viện dùng:** Hugging Face Inference API (`wonrax/phobert-base-vietnamese-sentiment`) · Laravel Mail

#### Nơi xử lý trong code
- **Model/Observer/Mail/Service:** `DanhGiaSanPham`, `app/Observers/DanhGiaSanPhamObserver.php`, `ReviewAlertMail`, `HuggingFaceService`.

#### Luồng xử lý chi tiết
1. Điều kiện đánh giá: khách **đã mua (đã thanh toán) và còn trong ngày mua**; chấm 1–5 sao + nội dung (`updateOrCreate`, 1 đánh giá/khách/sản phẩm).
2. **Phân tích cảm xúc bằng AI (điểm nhấn):** khi tạo/sửa nội dung, `DanhGiaSanPhamObserver` gọi `HuggingFaceService::analyzeSentiment()` dùng model **`wonrax/phobert-base-vietnamese-sentiment` (PhoBERT)** trên Hugging Face. Lấy nhãn điểm cao nhất (`POS/NEG/NEU`) → ghi vào cột `phan_tich_cam_xuc` (**Tích cực / Tiêu cực / Trung lập / Chưa phân tích**).
3. **Cảnh báo email có điều kiện:** chỉ khi cảm xúc **Tiêu cực/Trung lập** → gửi `ReviewAlertMail` tới **quản lý phụ trách ca bán đơn đó + tất cả chủ cửa hàng**.
4. Đánh giá **Tích cực** được dùng hiển thị testimonial (trang chủ lọc `phan_tich_cam_xuc = 'Tích cực'`).

---

### Chức năng 7: Đăng nhập bằng Google (OAuth)
**API/Thư viện dùng:** `laravel/socialite` · Google OAuth API

#### Nơi xử lý trong code
- **Controller:** `app/Http/Controllers/Auth/GoogleAuthController.php`.
- **Thư viện:** **`laravel/socialite`**, cấu hình `config('services.google')` (`GOOGLE_CLIENT_ID / CLIENT_SECRET / REDIRECT_URI`).

#### Luồng xử lý chi tiết
1. Khách bấm "Đăng nhập bằng Google" → Socialite redirect sang **Google OAuth**.
2. Google callback về `auth/google/callback` → Socialite lấy thông tin tài khoản Google.
3. Hệ thống tạo/khớp tài khoản `NguoiDung` và đăng nhập.

---

## CÔNG NGHỆ & API TÍCH HỢP (TỔNG HỢP)

### API bên ngoài (gọi qua HTTP)

| Tích hợp | Dùng cho chức năng | Chi tiết code |
|---|---|---|
| **OpenAI API** | Chatbot AI (trả lời + tinh chỉnh giỏ hàng JSON mode) | model `gpt-4o-mini`, `OPENAI_API_KEY` |
| **Hugging Face Inference API** (1) | Gọi món bằng **giọng nói** (speech-to-text) | model `openai/whisper-large-v3-turbo`, `HuggingFaceService::speechToText()` |
| **Hugging Face Inference API** (2) | **Phân tích cảm xúc đánh giá** (review) | model `wonrax/phobert-base-vietnamese-sentiment` (PhoBERT), `HuggingFaceService::analyzeSentiment()` |
| **OpenWeatherMap API** | Chatbot gợi ý món theo **thời tiết** | `api.openweathermap.org/data/2.5/weather`, `OPENWEATHERMAP_API_KEY`, có cache |
| **Nominatim (OpenStreetMap)** | **Geocoding** địa chỉ quán → tọa độ cho điểm danh GPS | `GeocodingService`, có cache |
| **PayOS** | **Thanh toán QR** online | thư viện `payos/payos`, `config('services.payos')`, `PAYOS_CLIENT_ID / API_KEY / CHECKSUM_KEY` |
| **Google OAuth** | **Đăng nhập bằng Google** | `laravel/socialite`, `GoogleAuthController` |
| **HTML5 Geolocation** (Web API trình duyệt) | Lấy tọa độ thiết bị khi điểm danh | `navigator.geolocation.getCurrentPosition` |

### Thư viện quan trọng (composer)

| Thư viện | Dùng cho |
|---|---|
| `payos/payos` | Cổng thanh toán PayOS |
| `laravel/socialite` | Đăng nhập Google |
| `simplesoftwareio/simple-qrcode` | Sinh mã **QR** (bàn ăn, điểm danh, thanh toán) |
| `phpoffice/phpspreadsheet` | **Xuất/nhập Excel** (kho nguyên liệu) |
| `intervention/image` | Xử lý/resize ảnh sản phẩm |
| `league/flysystem-aws-s3-v3` | Lưu trữ file trên S3 |

### Điểm nhấn kỹ thuật tự xây (framework/thuật toán, không phải thư viện ngoài)

- **Laravel Signed URL (HMAC-SHA256)** → QR điểm danh có chữ ký + hết hạn (chống chụp ảnh quét lại).
- **Công thức Haversine** (tự viết) → tính khoảng cách GPS để geofencing.
- **Queue + Mail** → email cảnh báo hết kho (`LowStockMail`) và đánh giá tiêu cực (`ReviewAlertMail`) gửi nền.
- **Observer Pattern** → tự động cảnh báo kho / phân tích cảm xúc khi có thay đổi.

---

## PHỤ LỤC — Bảng thuật ngữ / tên thực tế (để tránh sai khi làm slide)

| Khái niệm | Tên đúng trong code |
|---|---|
| Bảng chấm công | `cham_cong` (`nguoi_dung_id`, `ca_lam_viec_id`, `cham_cong_vao`, `cham_cong_ra`, `ghi_chu`) |
| Tồn kho | Tính động từ `lich_su_kho` (không có cột `so_luong_ton`) |
| Công thức món | `cong_thuc_san_pham` (`so_luong_can`) |
| Mail cảnh báo kho | `LowStockMail` (không phải `LowStockAlertMail`) |
| Thư viện Excel | `phpoffice/phpspreadsheet` (không phải Maatwebsite) |
| AI văn bản | OpenAI `gpt-4o-mini` |
| AI giọng nói | Hugging Face `openai/whisper-large-v3-turbo` |
| AI phân tích cảm xúc review | Hugging Face `wonrax/phobert-base-vietnamese-sentiment` |
| Cột lưu cảm xúc review | `danh_gia_san_pham.phan_tich_cam_xuc` (Tích cực/Tiêu cực/Trung lập/Chưa phân tích) |
| Cổng thanh toán | PayOS (`payos/payos`) |
| Bán kính GPS | mặc định 200 m (env `ATTENDANCE_GEO_RADIUS`) |
| Tham số bàn QR | route param `{table}` = ID bàn |
