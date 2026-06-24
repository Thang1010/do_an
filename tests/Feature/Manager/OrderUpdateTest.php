<?php

namespace Tests\Feature\Manager;

use App\Models\DonHang;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Manager\Concerns\InteractsWithOrders;
use Tests\TestCase;

/**
 * Unit/Feature test cho chức năng SỬA đơn hàng:
 *  - Manager\OrderController@edit   (GET  manager.orders.edit)
 *  - Manager\OrderController@update (PUT  manager.orders.update)
 */
class OrderUpdateTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithOrders;

    /** Tạo nhanh một đơn CHƯA thanh toán tại bàn để sửa. */
    private function makeEditableOrder(&$owner, &$table, &$product)
    {
        $owner   = $this->owner();
        $table   = $this->createTable('đang phục vụ');
        $product = $this->createProduct(['gia_goc' => 20000]);

        return $this->makeOrder([
            'nhan_vien_id' => $owner->id,
            'ban_an_id'    => $table->id,
            'items'        => [[
                'san_pham_id'  => $product->id,
                'ten_san_pham' => $product->ten_san_pham,
                'don_gia'      => 20000,
                'so_luong'     => 2,
            ]],
        ]);
    }

    // ───────────────────────── edit() ───────────────────────────────────────

    #[Test]
    public function mo_duoc_form_sua_don_chua_thanh_toan(): void
    {
        $order = $this->makeEditableOrder($owner, $table, $product);

        $this->actingAs($owner, 'nguoi_dung')
            ->get(route('manager.orders.edit', $order->id))
            ->assertOk()
            ->assertViewIs('manager.orders.edit');
    }

    #[Test]
    public function don_da_thanh_toan_chuyen_huong_ve_trang_chi_tiet(): void
    {
        $owner   = $this->owner();
        $table   = $this->createTable();
        $product = $this->createProduct();
        $order   = $this->makeOrder([
            'nhan_vien_id'          => $owner->id,
            'ban_an_id'             => $table->id,
            'trang_thai_thanh_toan' => 'đã thanh toán',
            'items'                 => [[
                'san_pham_id'  => $product->id,
                'ten_san_pham' => $product->ten_san_pham,
                'so_luong'     => 1,
            ]],
        ]);

        $this->actingAs($owner, 'nguoi_dung')
            ->get(route('manager.orders.edit', $order->id))
            ->assertRedirect(route('manager.orders.show', $order->id))
            ->assertSessionHas('warning');
    }

    // ───────────────────────── update() thành công ──────────────────────────

    #[Test]
    public function sua_duoc_so_luong_va_thay_the_chi_tiet(): void
    {
        $order = $this->makeEditableOrder($owner, $table, $product);

        $this->actingAs($owner, 'nguoi_dung')
            ->put(route('manager.orders.update', $order->id), [
                'ban_an_id' => $table->id,
                'items'     => [
                    ['san_pham_id' => $product->id, 'so_luong' => 5],
                ],
            ])
            ->assertRedirect(route('manager.orders.show', $order->id))
            ->assertSessionHas('success');

        // Chi tiết cũ (2) bị xóa, thay bằng chi tiết mới (5).
        $this->assertSame(1, $order->chiTietDonHang()->count());
        $this->assertDatabaseHas('chi_tiet_don_hang', [
            'don_hang_id' => $order->id,
            'san_pham_id' => $product->id,
            'so_luong'    => 5,
            'thanh_tien'  => 100000,
        ]);
        $this->assertDatabaseMissing('chi_tiet_don_hang', [
            'don_hang_id' => $order->id,
            'so_luong'    => 2,
        ]);
    }

    #[Test]
    public function them_mon_cap_nhat_tong_tien(): void
    {
        $order = $this->makeEditableOrder($owner, $table, $product);
        $product2 = $this->createProduct(['gia_goc' => 35000]);

        $this->actingAs($owner, 'nguoi_dung')
            ->put(route('manager.orders.update', $order->id), [
                'ban_an_id' => $table->id,
                'items'     => [
                    ['san_pham_id' => $product->id, 'so_luong' => 1],  // 20.000
                    ['san_pham_id' => $product2->id, 'so_luong' => 2], // 70.000
                ],
            ])
            ->assertRedirect(route('manager.orders.show', $order->id));

        $this->assertSame(2, $order->chiTietDonHang()->count());
        $this->assertEqualsWithDelta(90000, $order->fresh()->tong_tien, 0.01);
    }

    #[Test]
    public function doi_ban_se_giai_phong_ban_cu(): void
    {
        $order = $this->makeEditableOrder($owner, $oldTable, $product);
        $newTable = $this->createTable('trống');

        $this->actingAs($owner, 'nguoi_dung')
            ->put(route('manager.orders.update', $order->id), [
                'ban_an_id' => $newTable->id,
                'items'     => [['san_pham_id' => $product->id, 'so_luong' => 1]],
            ])
            ->assertRedirect(route('manager.orders.show', $order->id));

        $this->assertDatabaseHas('don_hang', ['id' => $order->id, 'ban_an_id' => $newTable->id]);
        // Bàn cũ không còn đơn chưa thanh toán → trống; bàn mới → đang phục vụ.
        $this->assertDatabaseHas('ban_an', ['id' => $oldTable->id, 'trang_thai' => 'trống']);
        $this->assertDatabaseHas('ban_an', ['id' => $newTable->id, 'trang_thai' => 'đang phục vụ']);
    }

    #[Test]
    public function sua_don_dat_truoc_van_giu_ban_null(): void
    {
        $owner   = $this->owner();
        $table   = $this->createTable();
        $product = $this->createProduct();
        $order   = $this->makeOrder([
            'nhan_vien_id' => $owner->id,
            'ban_an_id'    => null,
            'loai_don'     => 'đặt hàng trước',
            'items'        => [[
                'san_pham_id'  => $product->id,
                'ten_san_pham' => $product->ten_san_pham,
                'so_luong'     => 1,
            ]],
        ]);

        // Dù gửi kèm ban_an_id, controller vẫn ép null cho đơn đặt trước.
        $this->actingAs($owner, 'nguoi_dung')
            ->put(route('manager.orders.update', $order->id), [
                'ban_an_id' => $table->id,
                'items'     => [['san_pham_id' => $product->id, 'so_luong' => 3]],
            ])
            ->assertRedirect(route('manager.orders.show', $order->id));

        $this->assertDatabaseHas('don_hang', ['id' => $order->id, 'ban_an_id' => null]);
    }

    // ───────────────────────── update() bị chặn ─────────────────────────────

    #[Test]
    public function khong_sua_duoc_don_da_thanh_toan(): void
    {
        $owner   = $this->owner();
        $table   = $this->createTable();
        $product = $this->createProduct();
        $order   = $this->makeOrder([
            'nhan_vien_id'          => $owner->id,
            'ban_an_id'             => $table->id,
            'trang_thai_thanh_toan' => 'đã thanh toán',
            'items'                 => [[
                'san_pham_id'  => $product->id,
                'ten_san_pham' => $product->ten_san_pham,
                'so_luong'     => 1,
            ]],
        ]);

        $this->actingAs($owner, 'nguoi_dung')
            ->put(route('manager.orders.update', $order->id), [
                'ban_an_id' => $table->id,
                'items'     => [['san_pham_id' => $product->id, 'so_luong' => 9]],
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('chi_tiet_don_hang', [
            'don_hang_id' => $order->id,
            'so_luong'    => 9,
        ]);
    }

    #[Test]
    public function update_bat_buoc_co_mon(): void
    {
        $order = $this->makeEditableOrder($owner, $table, $product);

        $this->actingAs($owner, 'nguoi_dung')
            ->from(route('manager.orders.edit', $order->id))
            ->put(route('manager.orders.update', $order->id), [
                'ban_an_id' => $table->id,
                'items'     => [],
            ])
            ->assertSessionHasErrors('items');
    }

    #[Test]
    public function update_so_luong_phai_hop_le(): void
    {
        $order = $this->makeEditableOrder($owner, $table, $product);

        $this->actingAs($owner, 'nguoi_dung')
            ->from(route('manager.orders.edit', $order->id))
            ->put(route('manager.orders.update', $order->id), [
                'ban_an_id' => $table->id,
                'items'     => [['san_pham_id' => $product->id, 'so_luong' => 0]],
            ])
            ->assertSessionHasErrors('items.0.so_luong');
    }

    #[Test]
    public function update_kich_co_khong_thuoc_san_pham_bi_chan(): void
    {
        $order = $this->makeEditableOrder($owner, $table, $product);
        $orphanSize = $this->attachSize($this->createProduct(), 1.2, 'XL');

        $this->actingAs($owner, 'nguoi_dung')
            ->from(route('manager.orders.edit', $order->id))
            ->put(route('manager.orders.update', $order->id), [
                'ban_an_id' => $table->id,
                'items'     => [
                    ['san_pham_id' => $product->id, 'kich_co_id' => $orphanSize->id, 'so_luong' => 1],
                ],
            ])
            ->assertSessionHasErrors('items.0.kich_co_id');
    }

    #[Test]
    public function update_don_su_dung_ngay_bat_buoc_co_ban(): void
    {
        $order = $this->makeEditableOrder($owner, $table, $product);

        $this->actingAs($owner, 'nguoi_dung')
            ->from(route('manager.orders.edit', $order->id))
            ->put(route('manager.orders.update', $order->id), [
                // thiếu ban_an_id cho đơn sử dụng ngay
                'items' => [['san_pham_id' => $product->id, 'so_luong' => 1]],
            ])
            ->assertSessionHasErrors('ban_an_id');
    }

    #[Test]
    public function update_don_khong_ton_tai_tra_ve_404(): void
    {
        $owner   = $this->owner();
        $table   = $this->createTable();
        $product = $this->createProduct(); // payload hợp lệ để qua được validation

        $this->actingAs($owner, 'nguoi_dung')
            ->put(route('manager.orders.update', 999999), [
                'ban_an_id' => $table->id,
                'items'     => [['san_pham_id' => $product->id, 'so_luong' => 1]],
            ])
            ->assertNotFound();
    }

    #[Test]
    public function nhan_vien_khong_duoc_sua_don(): void
    {
        // Đơn do owner tạo, nhân viên cố sửa.
        $owner   = $this->owner();
        $table   = $this->createTable();
        $product = $this->createProduct();
        $order   = $this->makeOrder([
            'nhan_vien_id' => $owner->id,
            'ban_an_id'    => $table->id,
            'items'        => [[
                'san_pham_id'  => $product->id,
                'ten_san_pham' => $product->ten_san_pham,
                'so_luong'     => 1,
            ]],
        ]);

        $staff = $this->staff();

        $this->actingAs($staff, 'nguoi_dung')
            ->from('/')
            ->put(route('manager.orders.update', $order->id), [
                'ban_an_id' => $table->id,
                'items'     => [['san_pham_id' => $product->id, 'so_luong' => 7]],
            ])
            ->assertRedirect(); // bị middleware role chặn

        $this->assertDatabaseMissing('chi_tiet_don_hang', [
            'don_hang_id' => $order->id,
            'so_luong'    => 7,
        ]);
    }
}
