<?php

namespace Tests\Feature\Manager;

use App\Models\DonHang;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Manager\Concerns\InteractsWithOrders;
use Tests\TestCase;

/**
 * Unit/Feature test cho chức năng THÊM đơn hàng:
 * Manager\OrderController@store  (POST manager.orders.store)
 */
class OrderStoreTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithOrders;

    // ───────────────────────── Trường hợp thành công ─────────────────────────

    #[Test]
    public function chu_cua_hang_tao_duoc_don_su_dung_ngay_tai_ban(): void
    {
        $owner   = $this->owner();
        $table   = $this->createTable();
        $product = $this->createProduct(['gia_goc' => 20000]);

        $response = $this->actingAs($owner, 'nguoi_dung')
            ->from(route('manager.orders.index'))
            ->post(route('manager.orders.store'), [
                'loai_don'  => 'sử dụng ngay',
                'ban_an_id' => $table->id,
                'items'     => [
                    ['san_pham_id' => $product->id, 'so_luong' => 2],
                ],
            ]);

        $response->assertRedirect(route('manager.orders.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('don_hang', [
            'nhan_vien_id' => $owner->id,
            'ban_an_id'    => $table->id,
        ]);
        $this->assertDatabaseHas('chi_tiet_don_hang', [
            'san_pham_id'           => $product->id,
            'so_luong'              => 2,
            'don_gia'               => 20000,
            'thanh_tien'            => 40000,
            'tong_tien'             => 40000,
            'loai_don'              => 'sử dụng ngay',
            'trang_thai_thanh_toan' => 'chưa thanh toán',
        ]);
        // Bàn chuyển sang đang phục vụ sau khi có đơn chưa thanh toán.
        $this->assertDatabaseHas('ban_an', [
            'id'         => $table->id,
            'trang_thai' => 'đang phục vụ',
        ]);
    }

    #[Test]
    public function quan_ly_cung_tao_duoc_don(): void
    {
        $manager = $this->manager();
        $table   = $this->createTable();
        $product = $this->createProduct();

        $this->actingAs($manager, 'nguoi_dung')
            ->post(route('manager.orders.store'), [
                'loai_don'  => 'sử dụng ngay',
                'ban_an_id' => $table->id,
                'items'     => [['san_pham_id' => $product->id, 'so_luong' => 1]],
            ])
            ->assertRedirect(route('manager.orders.index'));

        $this->assertDatabaseCount('don_hang', 1);
    }

    #[Test]
    public function tao_don_co_kich_co_thi_nhan_he_so_gia(): void
    {
        $owner   = $this->owner();
        $table   = $this->createTable();
        $product = $this->createProduct(['gia_goc' => 20000]);
        $size    = $this->attachSize($product, 1.5, 'L');

        $this->actingAs($owner, 'nguoi_dung')
            ->post(route('manager.orders.store'), [
                'loai_don'  => 'sử dụng ngay',
                'ban_an_id' => $table->id,
                'items'     => [
                    ['san_pham_id' => $product->id, 'kich_co_id' => $size->id, 'so_luong' => 2],
                ],
            ])
            ->assertRedirect(route('manager.orders.index'));

        // 20000 * 1.5 = 30000 mỗi ly; 2 ly = 60000.
        $this->assertDatabaseHas('chi_tiet_don_hang', [
            'san_pham_id' => $product->id,
            'kich_co_id'  => $size->id,
            'ten_kich_co' => 'L',
            'don_gia'     => 30000,
            'thanh_tien'  => 60000,
        ]);
    }

    #[Test]
    public function uu_tien_gia_khuyen_mai_khi_co(): void
    {
        $owner   = $this->owner();
        $table   = $this->createTable();
        $product = $this->createProduct(['gia_goc' => 20000, 'gia_khuyen_mai' => 15000]);

        $this->actingAs($owner, 'nguoi_dung')
            ->post(route('manager.orders.store'), [
                'loai_don'  => 'sử dụng ngay',
                'ban_an_id' => $table->id,
                'items'     => [['san_pham_id' => $product->id, 'so_luong' => 1]],
            ]);

        $this->assertDatabaseHas('chi_tiet_don_hang', [
            'san_pham_id' => $product->id,
            'don_gia'     => 15000,
            'thanh_tien'  => 15000,
        ]);
    }

    #[Test]
    public function tao_don_dat_hang_truoc_khong_can_ban(): void
    {
        $owner   = $this->owner();
        $product = $this->createProduct();

        $this->actingAs($owner, 'nguoi_dung')
            ->post(route('manager.orders.store'), [
                'loai_don' => 'đặt hàng trước',
                'items'    => [['san_pham_id' => $product->id, 'so_luong' => 1]],
            ])
            ->assertRedirect(route('manager.orders.index'));

        $this->assertDatabaseHas('don_hang', ['ban_an_id' => null]);
        $this->assertDatabaseHas('chi_tiet_don_hang', [
            'san_pham_id' => $product->id,
            'loai_don'    => 'đặt hàng trước',
        ]);
    }

    #[Test]
    public function don_co_the_gan_voi_khach_hang(): void
    {
        $owner    = $this->owner();
        $table    = $this->createTable();
        $product  = $this->createProduct();
        $customer = $this->customer();

        $this->actingAs($owner, 'nguoi_dung')
            ->post(route('manager.orders.store'), [
                'loai_don'      => 'sử dụng ngay',
                'ban_an_id'     => $table->id,
                'nguoi_dung_id' => $customer->id,
                'items'         => [['san_pham_id' => $product->id, 'so_luong' => 1]],
            ]);

        $this->assertDatabaseHas('don_hang', [
            'nguoi_dung_id' => $customer->id,
        ]);
    }

    #[Test]
    public function luu_ghi_chu_mon(): void
    {
        $owner   = $this->owner();
        $table   = $this->createTable();
        $product = $this->createProduct();

        $this->actingAs($owner, 'nguoi_dung')
            ->post(route('manager.orders.store'), [
                'loai_don'  => 'sử dụng ngay',
                'ban_an_id' => $table->id,
                'items'     => [
                    ['san_pham_id' => $product->id, 'so_luong' => 1, 'ghi_chu_mon' => 'Ít đường, không đá'],
                ],
            ]);

        $this->assertDatabaseHas('chi_tiet_don_hang', [
            'san_pham_id' => $product->id,
            'ghi_chu_mon' => 'Ít đường, không đá',
        ]);
    }

    #[Test]
    public function tao_don_nhieu_mon_tinh_tong_dung(): void
    {
        $owner    = $this->owner();
        $table    = $this->createTable();
        $product1 = $this->createProduct(['gia_goc' => 20000]);
        $product2 = $this->createProduct(['gia_goc' => 35000]);

        $this->actingAs($owner, 'nguoi_dung')
            ->post(route('manager.orders.store'), [
                'loai_don'  => 'sử dụng ngay',
                'ban_an_id' => $table->id,
                'items'     => [
                    ['san_pham_id' => $product1->id, 'so_luong' => 2], // 40.000
                    ['san_pham_id' => $product2->id, 'so_luong' => 1], // 35.000
                ],
            ]);

        $order = DonHang::firstOrFail();
        $this->assertSame(2, $order->chiTietDonHang()->count());
        $this->assertEqualsWithDelta(75000, $order->tong_tien, 0.01);
    }

    #[Test]
    public function ma_don_hang_duoc_sinh_tu_dong(): void
    {
        $owner   = $this->owner();
        $table   = $this->createTable();
        $product = $this->createProduct();

        $this->actingAs($owner, 'nguoi_dung')
            ->post(route('manager.orders.store'), [
                'loai_don'  => 'sử dụng ngay',
                'ban_an_id' => $table->id,
                'items'     => [['san_pham_id' => $product->id, 'so_luong' => 1]],
            ]);

        $order = DonHang::firstOrFail();
        $this->assertNotEmpty($order->ma_don_hang);
        $this->assertStringStartsWith('DH', $order->ma_don_hang);
    }

    // ───────────────────────── Xuất kho khi tạo đơn ─────────────────────────

    #[Test]
    public function tao_don_xuat_kho_nguyen_lieu_theo_cong_thuc(): void
    {
        $owner      = $this->owner();
        $table      = $this->createTable();
        $product    = $this->createProduct();
        $ingredient = $this->createIngredient('Sữa');
        $this->addRecipe($product, $ingredient, 30); // mỗi ly cần 30
        $this->addStock($ingredient, 100);            // tồn 100

        $this->actingAs($owner, 'nguoi_dung')
            ->post(route('manager.orders.store'), [
                'loai_don'  => 'sử dụng ngay',
                'ban_an_id' => $table->id,
                'items'     => [['san_pham_id' => $product->id, 'so_luong' => 2]], // cần 60
            ])
            ->assertRedirect(route('manager.orders.index'));

        $this->assertDatabaseHas('lich_su_kho', [
            'nguyen_lieu_id' => $ingredient->id,
            'loai_giao_dich' => 'xuất kho',
            'so_luong'       => 60,
        ]);
    }

    #[Test]
    public function khong_tao_duoc_don_khi_thieu_ton_kho(): void
    {
        $owner      = $this->owner();
        $table      = $this->createTable();
        $product    = $this->createProduct();
        $ingredient = $this->createIngredient('Sữa');
        $this->addRecipe($product, $ingredient, 30);
        $this->addStock($ingredient, 10); // không đủ cho 60

        $this->actingAs($owner, 'nguoi_dung')
            ->from(route('manager.orders.index'))
            ->post(route('manager.orders.store'), [
                'loai_don'  => 'sử dụng ngay',
                'ban_an_id' => $table->id,
                'items'     => [['san_pham_id' => $product->id, 'so_luong' => 2]],
            ])
            ->assertSessionHasErrors('trang_thai');

        // Giao dịch bị rollback → không có đơn nào được tạo.
        $this->assertDatabaseCount('don_hang', 0);
        $this->assertDatabaseCount('chi_tiet_don_hang', 0);
    }

    // ───────────────────────── Kiểm tra dữ liệu (validation) ────────────────

    #[Test]
    public function bat_buoc_co_it_nhat_mot_mon(): void
    {
        $owner = $this->owner();
        $table = $this->createTable();

        $this->actingAs($owner, 'nguoi_dung')
            ->from(route('manager.orders.index'))
            ->post(route('manager.orders.store'), [
                'loai_don'  => 'sử dụng ngay',
                'ban_an_id' => $table->id,
                'items'     => [],
            ])
            ->assertSessionHasErrors('items');

        $this->assertDatabaseCount('don_hang', 0);
    }

    #[Test]
    public function don_su_dung_ngay_bat_buoc_chon_ban(): void
    {
        $owner   = $this->owner();
        $product = $this->createProduct();

        $this->actingAs($owner, 'nguoi_dung')
            ->from(route('manager.orders.index'))
            ->post(route('manager.orders.store'), [
                'loai_don' => 'sử dụng ngay',
                'items'    => [['san_pham_id' => $product->id, 'so_luong' => 1]],
            ])
            ->assertSessionHasErrors('ban_an_id');

        $this->assertDatabaseCount('don_hang', 0);
    }

    #[Test]
    public function loai_don_khong_hop_le_bi_chan(): void
    {
        $owner   = $this->owner();
        $table   = $this->createTable();
        $product = $this->createProduct();

        $this->actingAs($owner, 'nguoi_dung')
            ->from(route('manager.orders.index'))
            ->post(route('manager.orders.store'), [
                'loai_don'  => 'giao tận nơi', // không thuộc enum cho phép
                'ban_an_id' => $table->id,
                'items'     => [['san_pham_id' => $product->id, 'so_luong' => 1]],
            ])
            ->assertSessionHasErrors('loai_don');
    }

    #[Test]
    public function so_luong_phai_lon_hon_khong(): void
    {
        $owner   = $this->owner();
        $table   = $this->createTable();
        $product = $this->createProduct();

        $this->actingAs($owner, 'nguoi_dung')
            ->from(route('manager.orders.index'))
            ->post(route('manager.orders.store'), [
                'loai_don'  => 'sử dụng ngay',
                'ban_an_id' => $table->id,
                'items'     => [['san_pham_id' => $product->id, 'so_luong' => 0]],
            ])
            ->assertSessionHasErrors('items.0.so_luong');
    }

    #[Test]
    public function san_pham_khong_ton_tai_bi_chan(): void
    {
        $owner = $this->owner();
        $table = $this->createTable();

        $this->actingAs($owner, 'nguoi_dung')
            ->from(route('manager.orders.index'))
            ->post(route('manager.orders.store'), [
                'loai_don'  => 'sử dụng ngay',
                'ban_an_id' => $table->id,
                'items'     => [['san_pham_id' => 999999, 'so_luong' => 1]],
            ])
            ->assertSessionHasErrors('items.0.san_pham_id');
    }

    #[Test]
    public function kich_co_khong_thuoc_san_pham_bi_chan(): void
    {
        $owner       = $this->owner();
        $table       = $this->createTable();
        $product     = $this->createProduct();
        $otherProduct = $this->createProduct();
        // size tồn tại trong bảng kich_co (qua exists) nhưng KHÔNG gắn cho $product
        $orphanSize  = $this->attachSize($otherProduct, 1.2, 'XL');

        $this->actingAs($owner, 'nguoi_dung')
            ->from(route('manager.orders.index'))
            ->post(route('manager.orders.store'), [
                'loai_don'  => 'sử dụng ngay',
                'ban_an_id' => $table->id,
                'items'     => [
                    ['san_pham_id' => $product->id, 'kich_co_id' => $orphanSize->id, 'so_luong' => 1],
                ],
            ])
            ->assertSessionHasErrors('items.0.kich_co_id');

        $this->assertDatabaseCount('don_hang', 0);
    }

    #[Test]
    public function san_pham_gia_bang_khong_bi_chan(): void
    {
        $owner   = $this->owner();
        $table   = $this->createTable();
        $product = $this->createProduct(['gia_goc' => 0, 'gia_khuyen_mai' => 0]);

        $this->actingAs($owner, 'nguoi_dung')
            ->from(route('manager.orders.index'))
            ->post(route('manager.orders.store'), [
                'loai_don'  => 'sử dụng ngay',
                'ban_an_id' => $table->id,
                'items'     => [['san_pham_id' => $product->id, 'so_luong' => 1]],
            ])
            ->assertSessionHasErrors('items.0.san_pham_id');

        $this->assertDatabaseCount('don_hang', 0);
    }

    // ───────────────────────── Phân quyền ───────────────────────────────────

    #[Test]
    public function nhan_vien_khong_duoc_tao_don_o_khu_quan_ly(): void
    {
        $staff   = $this->staff();
        $table   = $this->createTable();
        $product = $this->createProduct();

        $this->actingAs($staff, 'nguoi_dung')
            ->from('/')
            ->post(route('manager.orders.store'), [
                'loai_don'  => 'sử dụng ngay',
                'ban_an_id' => $table->id,
                'items'     => [['san_pham_id' => $product->id, 'so_luong' => 1]],
            ])
            ->assertRedirect(); // middleware role chặn → redirect về trang trước

        $this->assertDatabaseCount('don_hang', 0);
    }

    #[Test]
    public function khach_chua_dang_nhap_bi_chan(): void
    {
        $this->postJson(route('manager.orders.store'), [
            'loai_don' => 'sử dụng ngay',
            'items'    => [['san_pham_id' => 1, 'so_luong' => 1]],
        ])->assertUnauthorized();

        $this->assertDatabaseCount('don_hang', 0);
    }
}
