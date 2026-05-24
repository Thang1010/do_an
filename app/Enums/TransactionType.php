<?php

namespace App\Enums;

enum TransactionType: string
{
    case NHAP_KHO = 'nhập kho';
    case XUAT_KHO = 'xuất kho';
    case DIEU_CHINH = 'điều chỉnh';

    /**
     * Tất cả các dạng chuỗi nhập kho có thể có trong DB.
     */
    public static function importValues(): array
    {
        return ['nhap', 'nhập', 'nhap kho', 'nhập kho'];
    }

    /**
     * Tất cả các dạng chuỗi xuất kho có thể có trong DB.
     */
    public static function exportValues(): array
    {
        return ['xuat', 'xuất', 'xuat kho', 'xuất kho'];
    }

    /**
     * Tất cả các dạng chuỗi điều chỉnh có thể có trong DB.
     */
    public static function adjustmentValues(): array
    {
        return ['điều chỉnh', 'dieu chinh'];
    }

    /**
     * Biểu thức SQL tính tồn kho (nhập - xuất + điều chỉnh).
     */
    public static function stockBalanceExpression(string $table = 'lich_su_kho'): string
    {
        $column = $table . '.loai_giao_dich';
        $quantity = $table . '.so_luong';

        $importList = "'" . implode("','", self::importValues()) . "'";
        $exportList = "'" . implode("','", self::exportValues()) . "'";
        $adjustmentList = "'" . implode("','", self::adjustmentValues()) . "'";

        return "SUM(CASE
            WHEN {$column} IN ({$importList}) THEN {$quantity}
            WHEN {$column} IN ({$exportList}) THEN -{$quantity}
            WHEN {$column} IN ({$adjustmentList}) THEN {$quantity}
            ELSE 0
        END)";
    }

    /**
     * Tất cả các giá trị giao dịch cho loại nhập hoặc xuất (dùng cho filter query).
     */
    public static function transactionTypeValues(string $type): array
    {
        return match ($type) {
            'import' => self::importValues(),
            'export' => array_merge(self::exportValues(), self::adjustmentValues()),
            default => [],
        };
    }

    public function label(): string
    {
        return $this->value;
    }
}
