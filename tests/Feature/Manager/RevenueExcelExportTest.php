<?php

namespace Tests\Feature\Manager;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Manager\Concerns\InteractsWithOrders;
use Tests\TestCase;

/**
 * Unit/Feature test cho chức năng XUẤT EXCEL báo cáo doanh thu (dữ liệu đơn hàng):
 * Manager\ReportController@exportRevenueExcel (GET manager.reports.revenue.export)
 *
 * Route được bảo vệ bằng middleware role:chủ cửa hàng.
 */
class RevenueExcelExportTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithOrders;

    private function seedOrdersToday(): void
    {
        $owner   = $this->owner();
        $product = $this->createProduct(['gia_goc' => 50000]);

        $this->makeOrder([
            'nhan_vien_id'          => $owner->id,
            'trang_thai_thanh_toan' => 'đã thanh toán',
            'items'                 => [[
                'san_pham_id'  => $product->id,
                'ten_san_pham' => $product->ten_san_pham,
                'don_gia'      => 50000,
                'so_luong'     => 2, // 100.000
            ]],
        ]);
    }

    #[Test]
    public function chu_cua_hang_tai_duoc_file_excel(): void
    {
        $owner = $this->owner();
        $this->seedOrdersToday();

        $response = $this->actingAs($owner, 'nguoi_dung')
            ->get(route('manager.reports.revenue.export'));

        $response->assertOk();
        $response->assertDownload();

        $disposition = $response->headers->get('content-disposition');
        $this->assertStringContainsString('Bao_Cao_Doanh_Thu_', (string) $disposition);
        $this->assertStringContainsString('.xlsx', (string) $disposition);
    }

    #[Test]
    public function xuat_duoc_khi_chua_co_don_nao(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner, 'nguoi_dung')
            ->get(route('manager.reports.revenue.export'))
            ->assertOk()
            ->assertDownload();
    }

    #[Test]
    public function xuat_duoc_voi_khoang_ngay_tuy_chon(): void
    {
        $owner = $this->owner();
        $this->seedOrdersToday();

        $from = now()->subDays(3)->toDateString();
        $to   = now()->toDateString();

        $this->actingAs($owner, 'nguoi_dung')
            ->get(route('manager.reports.revenue.export', ['from' => $from, 'to' => $to]))
            ->assertOk()
            ->assertDownload();
    }

    #[Test]
    public function quan_ly_khong_duoc_xuat_excel(): void
    {
        $manager = $this->manager();

        // Route chỉ cho chủ cửa hàng → quản lý bị middleware role chặn (redirect).
        $this->actingAs($manager, 'nguoi_dung')
            ->from(route('manager.dashboard'))
            ->get(route('manager.reports.revenue.export'))
            ->assertRedirect();
    }

    #[Test]
    public function nhan_vien_khong_duoc_xuat_excel(): void
    {
        $staff = $this->staff();

        $this->actingAs($staff, 'nguoi_dung')
            ->from('/')
            ->get(route('manager.reports.revenue.export'))
            ->assertRedirect();
    }

    #[Test]
    public function khach_chua_dang_nhap_bi_chan(): void
    {
        $this->getJson(route('manager.reports.revenue.export'))
            ->assertUnauthorized();
    }
}
