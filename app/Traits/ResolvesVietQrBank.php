<?php

namespace App\Traits;

use Illuminate\Support\Str;

/**
 * Trait chuyển đổi tên ngân hàng sang mã VietQR.
 *
 * Dùng trong các Controller tạo QR thanh toán (Manager\OrderController,
 * Manager\TableController, Staff\TableController, Staff\OrderController)
 * để tránh trùng lặp code resolveVietQrBankCode().
 */
trait ResolvesVietQrBank
{
    protected function resolveVietQrBankCode(?string $bankName): string
    {
        $raw = trim((string) $bankName);
        if ($raw === '') {
            return '';
        }

        $normalized = Str::of($raw)
            ->ascii()
            ->upper()
            ->replaceMatches('/[^A-Z0-9]/', '')
            ->value();

        $map = [
            'VCB' => 'VCB',
            'VIETCOMBANK' => 'VCB',
            'BIDV' => 'BIDV',
            'VIETINBANK' => 'ICB',
            'ICB' => 'ICB',
            'AGRIBANK' => 'VBA',
            'VBA' => 'VBA',
            'TECHCOMBANK' => 'TCB',
            'TCB' => 'TCB',
            'MB' => 'MB',
            'MBBANK' => 'MB',
            'ACB' => 'ACB',
            'SACOMBANK' => 'STB',
            'STB' => 'STB',
            'VPBANK' => 'VPB',
            'VPB' => 'VPB',
            'PVCOMBANK' => 'PVCB',
            'PVCBANK' => 'PVCB',
            'PVCB' => 'PVCB',
            'PVC' => 'PVCB',
            'TPBANK' => 'TPB',
            'TPB' => 'TPB',
        ];

        return $map[$normalized] ?? $normalized;
    }
}
