# Database Schema Restructuring Plan

## Phase 1: Migration Changes

### 1. san_pham (sản phẩm)
- [x] `ten_san_pham` → string(50) (was 150)
- [x] `mo_ta` → string(150) nullable (was text)
- [x] Add `mo_ta_chi_tiet` → string(300) nullable
- [x] `nhiet_do` → enum ['nóng', 'lạnh'] (was json)
- [x] `gia_goc` → float default(0) (was decimal)
- [x] `gia_khuyen_mai` → float default(0) (was decimal nullable)

### 2. danh_muc
- [x] `ten_danh_muc` → string(50) (was 150)
- [x] `slug` → string(60) (was 180)
- [x] `mo_ta` → string(300) nullable (was text)

### 3. ban_an
- [x] Already mostly correct, change enum to include 'đặt hàng' (was 'đã đặt')

### 4. voucher
- [x] `ma_voucher` → string(30) (was 50)
- [x] `ten_voucher` → string(50) unique (was 150, not unique)
- [x] `gia_tri_giam` → float default(0)
- [x] `don_toi_thieu` → float default(0)
- [x] `giam_toi_da` → float default(0)
- [x] `so_luong` → int default(0) ✓
- [x] DROP `da_su_dung`

### 5. nguyen_lieu
- [x] `ten_nguyen_lieu` → string(40) (was 150)
- [x] `muc_dich_su_dung` → string(40) (was 120)

### 6. chuc_vu
- [x] `ten_chuc_vu` → string(40) (was 100)
- [x] `vai_tro_ap_dung` → enum ['nhân viên', 'quản lý'] default 'nhân viên'
- [x] `mo_ta_chuc_vu` → string(50) (was text)
- [x] `luong_co_ban` → float default(0)
- [x] `loai_hinh_lam_viec` → enum ['toàn thời gian', 'bán thời gian']
- [x] `luong_theo_gio` → float default(0)

### 7. nguoi_dung
- [x] DROP `ho_ten`
- [x] `email` → string(60) unique (was 150)
- [x] DROP `so_dien_thoai`
- [x] `mat_khau` → string (validate 8-20 chars in request)
- [x] `trang_thai` → enum ['hoạt động', 'ngưng hoạt động'] (remove 'bị khóa')
- [x] DROP `anh_dai_dien`

### 8. ho_so_khach_hang
- [x] ADD `ho_ten` → string(70) nullable
- [x] `gioi_tinh` → enum ['nam', 'nữ'] (remove 'khác')
- [x] Rename `avatar` → `anh_dai_dien` → string nullable
- [x] DROP `tong_chi_tieu`

### 9. ho_so_nhan_vien
- [x] ADD `ho_ten` → string(70) nullable
- [x] ADD `ngay_sinh` → date nullable
- [x] ADD `dia_chi_tam_chu` → string(150) nullable
- [x] DROP `ma_nhan_vien`
- [x] ADD `so_dien_thoai` → string(10) nullable
- [x] Rename `avatar` → `anh_dai_dien` → string nullable

### 10. ho_so_quan_ly
- [x] DROP `ma_quan_ly`
- [x] Rename `avatar` → `anh_dai_dien` → string nullable

### 11. cua_hang
- [x] `ten_cua_hang` → string(30) (was 150)
- [x] DROP `email`
- [x] `so_dien_thoai` → string(10) (was 20)
- [x] `dia_chi` → string(150) (was 255)
- [x] `so_tai_khoan` → string(20) (was 50)
- [x] `ngan_hang` → enum ['MBBank']
- [x] `mo_ta` → string(150) (was text)

### 12. chot_ca
- [x] `so_tien_dau_ca` → float default(0)
- [x] `ghi_chu` → string(150) (was text)

### 13. ca_lam_viec
- [x] `ten_ca` → string(50) (was 100)

### 14. cham_cong
- [x] Rename `check_in_luc` → `cham_cong_vao` date nullable
- [x] Rename `check_out_luc` → `cham_cong_ra` date nullable
- [x] `ghi_chu` → string(150) (was text)

### 15. DROP bang_luong table and model

### 16. don_hang
- [x] DROP `loai_don`
- [x] DROP `trang_thai_thanh_toan`
- [x] DROP `phuong_thuc_thanh_toan`
- [x] DROP `tam_tinh`
- [x] DROP `so_tien_giam`
- [x] DROP `tong_tien`
- [x] DROP `ghi_chu`
- [x] DROP `ten_khach_hang`, `so_dien_thoai_khach`, `dia_chi_giao_hang`

### 17. danh_gia_san_pham
- [x] `noi_dung` → string(300) (was text)

### 18. kich_co
- [x] ADD `he_so_gia` → float default(0) (0-50)
- [x] `mo_ta` → string(50) (was text)

### 19. DROP san_pham_kich_co table and model
- san_pham now connects directly to kich_co

### 20. chi_tiet_don_hang
- [x] ADD `loai_don` enum ['đặt trực tuyến', 'mua tại quán'] default 'đặt trực tuyến'
- [x] ADD `trang_thai_thanh_toan` enum ['chưa thanh toán', 'đã thanh toán'] default 'chưa thanh toán'
- [x] ADD `phuong_thuc_thanh_toan` enum ['tiền mặt', 'chuyển khoản'] nullable
- [x] ADD `so_tien_giam` float default(0)
- [x] ADD `tong_tien` float default(0)
- [x] DROP `ban_an_id` foreign key

### 21. notifications → thong_bao (rename table, Vietnamese columns)

## Phase 2: Model Updates
- [x] DonHang.php - virtual accessors for trang_thai_thanh_toan, phuong_thuc_thanh_toan, loai_don, tam_tinh, so_tien_giam, tong_tien, ghi_chu; updatePaymentStatus() method; scopeWherePayStatus, scopeWhereLoaiDon
- [x] KichCo.php - fillable includes san_pham_id; sanPham() belongsTo; chiTietDonHang() hasMany kept
- [x] SanPham.php - kichCo() hasMany KichCo with san_pham_id FK; sanPhamKichCo() alias for backward compat
- [x] ThanhToan.php - fixed Schema::hasTable('thong_bao')
- [x] NguoiDung.php - getHoTenAttribute() reads from hoSoKhachHang or hoSoNhanVien

## Phase 3: Controller Updates
- [x] Auth/LoginController.php - removed so_dien_thoai login, removed bi_khoa check
- [x] Services/PaymentService.php - freeTableIfAllPaid uses whereHas chiTietDonHang
- [x] Services/TableStatusService.php - isBookingOrder checks loai_don; queries use whereHas
- [x] Customer/CartController.php - removed guest checkout, price calc uses KichCo.he_so_gia, createOrder sets payment fields on chi_tiet_don_hang
- [x] Customer/OrderController.php - payment status filters use whereHas; chi_tiet_don_hang fields updated
- [x] Customer/HomeController.php - with('kichCo') not sanPhamKichCo.kichCo
- [x] Customer/MenuController.php - with(['danhMuc','kichCo'])
- [x] Customer/ChatbotController.php - removed SanPhamKichCo; uses KichCo::whereIn('san_pham_id')
- [x] Staff/TableController.php - all payment status queries use whereHas; chi_tiet_don_hang fields set on creation
- [x] Manager/TableController.php - all payment status queries use whereHas; releaseTable, generatePaymentQr, addItem fixed
- [x] Manager/OrderController.php - payment status queries use whereHas
- [x] Manager/ProductController.php - syncProductSizes creates product-specific KichCo with san_pham_id; edit/create load product-specific sizes

## Phase 4: View Updates
- [x] customer/dashboard.blade.php - sanPhamKichCo → kichCo data-sizes; review ho_ten via hoSoKhachHang
- [x] customer/menu/index.blade.php - sanPhamKichCo → kichCo sizes
- [x] customer/menu/show.blade.php - sanPhamKichCo → kichCo sizes and buttons; review ho_ten via hoSoKhachHang
- [x] customer/cart/index.blade.php - guest section replaced with login prompt; so_dien_thoai via hoSoKhachHang
- [x] staff/tables/partials/left-panel.blade.php - sanPhamKichCo → kichCo; customerName via hoSoKhachHang
- [x] staff/tables/show.blade.php - nguoiDung ho_ten via hoSoKhachHang; nhanVien ho_ten via hoSoNhanVien
- [x] staff/orders/index.blade.php - nguoiDung ho_ten via hoSoKhachHang
- [x] staff/orders/show.blade.php - nguoiDung ho_ten via hoSoKhachHang; tam_tinh → tong_tien
- [x] staff/shifts/show.blade.php - nguoiDung ho_ten via hoSoNhanVien
- [x] manager/orders/show.blade.php - removed SanPhamKichCo lookup; customerName via hoSoKhachHang; nhanVien via hoSoNhanVien
- [x] manager/orders/index.blade.php - nguoiDung ho_ten via hoSoKhachHang; removed dia_chi_giao_hang field
- [x] manager/dashboard.blade.php - nguoiDung ho_ten via hoSoKhachHang
- [x] manager/tables/show.blade.php - customerName/phone via hoSo*
- [x] manager/products/create.blade.php - sanPhamKichCo → kichCo for size population
- [x] manager/vouchers/show.blade.php - nguoiDung ho_ten via hoSoKhachHang
- [x] manager/positions/show.blade.php - nguoiDung ho_ten via hoSoNhanVien
- [x] manager/shifts/attendance.blade.php - nguoiDung ho_ten via hoSoNhanVien

## Phase 5: Database
- [x] php artisan migrate:fresh - all 35 migrations ran successfully
- [x] php artisan db:seed - seeders ran without errors
