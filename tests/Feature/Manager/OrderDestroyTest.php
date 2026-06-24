<?php

namespace Tests\Feature\Manager;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Manager\Concerns\InteractsWithOrders;
use Tests\TestCase;

/**
 * Unit/Feature test cho chức năng XÓA đơn hàng:
 * Manager\OrderController@destroy (DELETE manager.orders.destroy)
 */
class OrderDestroyTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithOrders;

    #[Test]
    public function chu_cua_hang_xoa_duoc_don_va_cap_nhat_ban(): void
    {
        $owner   = $this->owner();
        $table   = $this->createTable();
        $product = $this->createProduct();
        $order   = $this->makeOrder([
            'nhan_vien_id' => $owner->id,
            'ban_an_id'    => $table->id,
            'items'        => [[
                'san_pham_id'  => $product->id,
                'ten_san_pham' => $product->ten_san_pham,
                'so_luong'     => 2,
            ]],
        ]);
        // Bàn đang phục vụ trước khi xóa.
        $table->update(['trang_thai' => 'đang phục vụ']);

        $this->actingAs($owner, 'nguoi_dung')
            ->delete(route('manager.orders.destroy', $order->id))
            ->assertRedirect(route('manager.orders.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('don_hang', ['id' => $order->id]);
        $this->assertDatabaseMissing('chi_tiet_don_hang', ['don_hang_id' => $order->id]);
        // Bàn trở lại trống sau khi xóa đơn.
        $this->assertDatabaseHas('ban_an', ['id' => $table->id, 'trang_thai' => 'trống']);
    }

    #[Test]
    public function xoa_don_thi_xoa_luon_ban_ghi_thanh_toan(): void
    {
        $owner   = $this->owner();
        $product = $this->createProduct();
        $order   = $this->makeOrder([
            'nhan_vien_id' => $owner->id,
            'items'        => [[
                'san_pham_id'  => $product->id,
                'ten_san_pham' => $product->ten_san_pham,
                'so_luong'     => 1,
            ]],
        ]);
        $payment = $this->makePayment($order, 'chưa thanh toán');

        $this->actingAs($owner, 'nguoi_dung')
            ->delete(route('manager.orders.destroy', $order->id))
            ->assertRedirect(route('manager.orders.index'));

        $this->assertDatabaseMissing('thanh_toan', ['id' => $payment->id]);
    }

    #[Test]
    public function xoa_don_hoan_lai_nguyen_lieu_vao_kho(): void
    {
        $owner      = $this->owner();
        $product    = $this->createProduct();
        $ingredient = $this->createIngredient('Sữa');
        $this->addRecipe($product, $ingredient, 30); // mỗi ly 30

        $order = $this->makeOrder([
            'nhan_vien_id' => $owner->id,
            'items'        => [[
                'san_pham_id'  => $product->id,
                'ten_san_pham' => $product->ten_san_pham,
                'so_luong'     => 2, // tổng 60
            ]],
        ]);

        $this->actingAs($owner, 'nguoi_dung')
            ->delete(route('manager.orders.destroy', $order->id))
            ->assertRedirect(route('manager.orders.index'));

        // Hoàn kho được ghi nhận là một giao dịch "điều chỉnh" với đúng số lượng.
        $this->assertDatabaseHas('lich_su_kho', [
            'nguyen_lieu_id' => $ingredient->id,
            'loai_giao_dich' => 'điều chỉnh',
            'so_luong'       => 60,
        ]);
    }

    #[Test]
    public function xoa_don_khong_ton_tai_tra_ve_404(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner, 'nguoi_dung')
            ->delete(route('manager.orders.destroy', 999999))
            ->assertNotFound();
    }

    #[Test]
    public function nhan_vien_khong_duoc_xoa_don(): void
    {
        $owner   = $this->owner();
        $product = $this->createProduct();
        $order   = $this->makeOrder([
            'nhan_vien_id' => $owner->id,
            'items'        => [[
                'san_pham_id'  => $product->id,
                'ten_san_pham' => $product->ten_san_pham,
                'so_luong'     => 1,
            ]],
        ]);

        $staff = $this->staff();

        $this->actingAs($staff, 'nguoi_dung')
            ->from('/')
            ->delete(route('manager.orders.destroy', $order->id))
            ->assertRedirect(); // middleware role chặn

        $this->assertDatabaseHas('don_hang', ['id' => $order->id]);
    }
}
