# 📋 Tổng hợp & Review Hệ thống Quản lý Cafe (CafeTea)

> Tài liệu tổng quan kiến trúc, chức năng và đánh giá chất lượng code.
> Ngày tạo: 2026-06-13 · Nhánh: `main`

---

## 1. Tổng quan công nghệ (Tech Stack)

| Thành phần | Công nghệ | Phiên bản |
|---|---|---|
| Backend Framework | **Laravel** | 13.1.1 |
| Ngôn ngữ | **PHP** | ^8.3 |
| Cơ sở dữ liệu | **MySQL** | (qua Eloquent) |
| Frontend Build | **Vite** | 8.0 |
| CSS | **Tailwind CSS** | 4.0 |
| JS | Axios + Blade (server-rendered) | — |
| Cổng thanh toán | **PayOS** (QR/chuyển khoản/thẻ) | — |
| AI Chatbot | **OpenAI** (gpt-4o-mini) + OpenWeatherMap | — |
| Đăng nhập | Email/Password + **Google OAuth** (Socialite) | — |
| Lưu trữ ảnh | **AWS S3** (`xmcoffee-images`) + Intervention Image | — |
| Xuất Excel | PhpSpreadsheet | — |
| Ngôn ngữ chính | Tiếng Việt (`vi`) | — |

**Quy ước đặt tên:** Toàn bộ domain model dùng **tên tiếng Việt** (`NguoiDung`, `SanPham`, `DonHang`, `BanAn`, `CaLamViec`...). Đây là đặc điểm cần lưu ý khi onboard người mới.

---

## 2. Phân quyền & Vai trò (RBAC)

Hệ thống có **4 vai trò** lưu ở cột `nguoi_dung.vai_tro`:

| Vai trò | Mô tả | Quyền chính |
|---|---|---|
| `chủ cửa hàng` | Chủ cửa hàng (Owner) | Toàn quyền + quản lý nguyên liệu + duyệt yêu cầu + quản lý admin |
| `quản lý` | Quản lý (Manager) | Hầu hết tính năng quản lý, KHÔNG sửa/xóa nguyên liệu, KHÔNG xem admin |
| `nhân viên` | Nhân viên (Staff) | POS bàn, gọi món, thanh toán, chấm công, chi tiêu cá nhân |
| `khách hàng` | Khách hàng (Customer) | Duyệt menu, giỏ hàng, đặt món, lịch sử đơn, voucher |

**Middleware kiểm soát:**
- `EnsureUserRole` (alias `role`) — kiểm tra `vai_tro` theo danh sách phân tách dấu phẩy (vd `role:quản lý,chủ cửa hàng`), abort 403 nếu không hợp lệ.
- `CheckStartingCash` — bắt buộc có tiền đầu ca (`ChotCa`) trước khi nhập/xuất kho hoặc ghi chi tiêu.

**Cấu trúc route:** `routes/web.php` (public + customer) → include `auth.php`, `manager.php` (prefix `/manager`), `staff.php` (prefix `/staff`).

---

## 3. Mô hình nghiệp vụ (Domain Model)

**32 Models · 36 Migrations · 9 Enums**

### 3.1. Người dùng & Hồ sơ
- `NguoiDung` (`nguoi_dung`) — model auth chính. Quan hệ: hasOne 3 loại hồ sơ, hasMany đơn hàng/voucher/đánh giá/ca làm, belongsTo `CuaHang`, morphMany `ThongBao`.
- `HoSoKhachHang`, `HoSoNhanVien`, `HoSoQuanLy` — hồ sơ chi tiết theo vai trò.
- `CuaHang` (`cua_hang`) — cửa hàng (hỗ trợ multi-tenant qua `cua_hang_id`).
- `ChucVu` (`chuc_vu`) — chức vụ + lương cơ bản/theo giờ.

### 3.2. Sản phẩm & Kho
- `SanPham` (`san_pham`) — sản phẩm, có nhiều ảnh, size (n-n qua `san_pham_kich_co`), công thức.
- `DanhMuc` (`danh_muc`) — danh mục.
- `KichCo` (`kich_co`) — size (đã refactor thành từ điển dùng chung qua bảng pivot).
- `HinhAnhSanPham` — ảnh sản phẩm.
- `NguyenLieu` (`nguyen_lieu`), `CongThucSanPham` (định lượng), `LichSuKho` (sổ cái xuất/nhập/điều chỉnh — tự động cập nhật trạng thái sản phẩm theo tồn kho).

### 3.3. Đơn hàng & Thanh toán
- `DonHang` (`don_hang`) — đơn hàng. **Lưu ý kiến trúc:** trạng thái/loại đơn/tổng tiền được lưu ở cấp **dòng chi tiết** (`chi_tiet_don_hang`), không ở đơn — `DonHang` dùng accessor ảo để đọc.
- `ChiTietDonHang` — dòng món (giữ trạng thái thanh toán, loại đơn, giảm giá, tổng tiền).
- `ThanhToan` (`thanh_toan`) — bản ghi thanh toán (QR, ngân hàng, mã giao dịch). Tự cập nhật trạng thái đơn + gửi thông báo khi thành công.
- `Voucher` + `VoucherNguoiDung` — khuyến mãi & gán cho khách.

### 3.4. Bàn & Đánh giá
- `BanAn` (`ban_an`) — bàn ăn.
- `DanhGiaSanPham` — đánh giá sao + nội dung.

### 3.5. Ca làm & Tài chính
- `CaLamViec` (`ca_lam_viec`), `ChamCong` (`cham_cong`), `ChotCa` (`chot_ca` — chốt ca + đếm tiền), `ChiTieu` (`chi_tieu`).

### 3.6. Chat AI
- `PhienChat`, `TinNhanChat`, `LichSuGoiAi` (lưu token + chi phí ước tính mỗi lần gọi AI).

### 3.7. Thông báo & Yêu cầu
- `ThongBao` (extends DatabaseNotification, PK UUID), `YeuCauNguyenLieu` (workflow duyệt nhập nguyên liệu).

### 3.8. Xác thực
- `EmailVerificationCode`, `PendingCustomerRegistration`.

### Enums (9)
`UserRole`, `UserStatus`, `OrderType`, `PaymentStatus`, `PaymentMethod`, `ProductStatus`, `TableStatus`, `TransactionType`, và `OrderStatus` (**đã deprecated** — enum rỗng, trạng thái chuyển sang theo dõi qua thanh toán).

---

## 4. Lớp ứng dụng (Application Layer)

### Services (6) — `app/Services/`
| Service | Trách nhiệm |
|---|---|
| `PaymentService` | Đồng bộ thanh toán, sinh QR VietQR |
| `OrderNotificationService` | Thông báo đơn (đặt/hủy/cập nhật) |
| `OrderInventoryService` | Trừ kho khi đặt đơn |
| `ShiftService` | Tạo/gán/chấm công/chốt ca |
| `TableStatusService` | Chuyển trạng thái bàn |
| `VoucherAssignmentService` | Gán & kiểm tra điều kiện voucher |

### Traits (4) — `app/Traits/`
`CalculatesStock`, `GeneratesOrderCode`, `NormalizesPayment`, `ResolvesVietQrBank`.

### Khác
- **Policies (3):** `OrderPolicy`, `ShiftPolicy`, `UserPolicy` (đăng ký ở `AppServiceProvider`).
- **Notifications (14):** xác thực email, đơn hàng (đặt/hủy/cập nhật), QR thanh toán, ca làm (gán/chốt/nhắc checkout muộn), duyệt tài khoản, reset mật khẩu...
- **Mail (3):** `CustomerOrderPaidMail`, `OrderPaidMail`, `NextWeekScheduleMail`.
- **Console Command (1):** `CheckLateCheckout` (nhắc checkout muộn).

---

## 5. Tích hợp ngoài (Integrations)

### 5.1. Thanh toán
- **PayOS** (`CartController`): cổng thanh toán duy nhất — tạo payment request có chữ ký, sinh QR chuyển khoản, return/cancel handler, **polling trạng thái server-side** (gọi API PayOS `GET /v2/payment-requests/{id}` qua `paymentStatusAjax`) để tự xác nhận đơn đã thanh toán, gửi email khi thành công.

### 5.2. Chatbot AI (`ChatbotController`)
- OpenAI gpt-4o-mini, **ngữ cảnh thông minh:** chỉ gợi ý món còn bán, biết món yêu thích của khách, best-seller theo doanh số, gợi ý theo nguyên liệu (tiểu đường/dị ứng/dạ dày), **tích hợp thời tiết** (OpenWeatherMap → gợi ý đồ nóng/lạnh).
- Chặn câu hỏi ngoài phạm vi & lộ công thức. Lưu lịch sử 8 tin nhắn, theo dõi token/chi phí.

---

## 6. Giao diện (Views)

Blade server-rendered, mỗi khu vực có layout riêng:
- **Customer** (~26 views): cart, chatbot, menu, orders, points, profile, reviews, vouchers.
- **Manager** (~52 views): dashboard, products, categories, inventory, ingredients (+requests), shifts, salary/payroll, reports, vouchers, users (admins/staffs/customers), payment-config, shift-close.
- **Staff** (~13 views): tables (POS), orders, shifts, expenses, customers, profile.
- **Auth** (6), **Emails** (3), **Guest** layout.

---

## 7. ✅ Điểm mạnh

1. **Kiến trúc phân tầng rõ ràng** — Controllers gọn nhờ tách Services, Traits, Form Requests, Policies. Đây là điểm rất tốt cho dự án Laravel quy mô này.
2. **Sử dụng Enum (PHP 8.1+) bài bản** — có method tiện ích (`normalize()`, `label()`, `staffRoles()`...), giảm magic string.
3. **RBAC nhất quán** qua middleware tập trung, dễ audit.
4. **Nghiệp vụ phong phú & thực tế** — POS, kho theo công thức, chấm công/chốt ca/đếm tiền, payroll, đa cổng thanh toán, AI chatbot có ngữ cảnh.
5. **Event-driven qua model `booted()`** — tự động cập nhật tồn kho, trạng thái bàn, gửi thông báo.
6. **Bảo mật cơ bản tốt:** `.env` **không** bị commit (đã gitignore đúng), mật khẩu hash, có verify email + OAuth.

---

## 8. ⚠️ Vấn đề & Khuyến nghị

### 🔴 Ưu tiên cao
| # | Vấn đề | Khuyến nghị |
|---|---|---|
| 1 | **Hầu như không có test** — chỉ 2 `ExampleTest` mặc định. Nghiệp vụ tài chính (thanh toán, kho, lương) không có test. | Viết Feature test cho luồng quan trọng: đặt đơn → trừ kho → thanh toán → chốt ca. Rủi ro hồi quy rất cao. |
| 2 | **Thiếu Seeder/Factory** — `database/seeders` & `factories` trống. | Tạo seeder dữ liệu mẫu (vai trò, sản phẩm, bàn) để dev/test + demo nhanh. |
| 3 | **File rác trong thư mục gốc:** `test_avatar.php`, `db_records.txt`, `implementation_plan.md`. `test_avatar.php` là script test lộ thiên có thể truy cập web nếu nằm trong public path. | Xóa khỏi repo (chưa được track nên chỉ cần xóa local) hoặc chuyển vào `tests/`. |

### 🟡 Ưu tiên trung bình
| # | Vấn đề | Khuyến nghị |
|---|---|---|
| 4 | **Controller quá lớn (God Controller)** — `UserController` 934 dòng, `InventoryController` 875, `Staff/TableController` 822, `ShiftController` 788, `CartController` 705. | Tiếp tục tách logic xuống Service/Action class. UserController nên tách theo nhóm (admin/staff/customer/approval). |
| 5 | **Model `User.php` mặc định vẫn còn** song song với `NguoiDung` — dễ gây nhầm lẫn guard/auth. | Xác nhận guard đang dùng, cân nhắc xóa `User` nếu không dùng. |
| 6 | **Trạng thái đơn lưu ở dòng chi tiết** (`chi_tiet_don_hang`) thay vì đơn — accessor ảo phức tạp, dễ sai khi 1 đơn có nhiều dòng khác trạng thái. | Ghi tài liệu rõ quy ước này; cân nhắc bảng tổng hợp ở `don_hang` nếu nghiệp vụ cho phép. |
| 7 | **Migration sửa cấu trúc gần đây** (size n-n, bỏ `OrderStatus`). | Đảm bảo môi trường production đã chạy migration đồng bộ. |

### 🟢 Cải thiện thêm
- Bổ sung **CI** (`.github/` đã có — kiểm tra có chạy Pint + test không).
- Chạy **Laravel Pint** (`composer require laravel/pint` đã có) định kỳ để chuẩn hóa code style.
- Cân nhắc **index DB** cho các cột truy vấn nhiều (`don_hang.ma_don_hang`, `nguoi_dung.email`, `lich_su_kho.nguyen_lieu_id`).
- PayOS hiện xác nhận qua **return URL + polling**. Cân nhắc bổ sung **webhook PayOS** (server-to-server) để xác nhận chắc chắn ngay cả khi khách đóng trình duyệt trước khi quay lại.

---

## 9. Thống kê nhanh

```
Controllers : 45  (Auth 7 · Customer 10 · Manager 19 · Staff 8 · Webhook 1)
Models      : 32
Migrations  : 36
Enums       :  9  (1 deprecated)
Services    :  6
Traits      :  4
Policies    :  3
Notifications: 14
Mail        :  3
Tests       :  2  (chỉ ExampleTest ⚠️)
Tổng LOC Controllers: ~11,800 dòng
```

---

## 10. Kết luận

Đây là một hệ thống **quy mô lớn, nghiệp vụ thực tế và kiến trúc tương đối tốt** cho một dự án Laravel — phân tầng Service/Trait/Policy hợp lý, dùng Enum bài bản, RBAC rõ ràng, tích hợp thanh toán & AI đầy đủ.

**Rủi ro lớn nhất là thiếu test** trên các luồng tài chính/kho, cộng với một số controller quá lớn. Ưu tiên trước mắt: **(1) viết test cho luồng tiền & kho, (2) dọn file rác ở thư mục gốc, (3) tách nhỏ các controller > 700 dòng.**
