<?php

namespace App\Traits;

use App\Enums\TransactionType;
use App\Models\LichSuKho;

/**
 * Trait tính toán tồn kho nguyên liệu.
 *
 * Dùng trong Manager\OrderController và Manager\InventoryController
 * để tránh trùng lặp code stockBalanceExpression() và currentStock().
 */
trait CalculatesStock
{
    protected function stockBalanceExpression(string $table = 'lich_su_kho'): string
    {
        return TransactionType::stockBalanceExpression($table);
    }

    protected function currentStock(int $ingredientId): float
    {
        $balanceExpression = $this->stockBalanceExpression('lich_su_kho');

        return (float) (LichSuKho::query()
            ->where('nguyen_lieu_id', $ingredientId)
            ->selectRaw("COALESCE({$balanceExpression}, 0) as so_luong")
            ->value('so_luong') ?? 0);
    }

    protected function currentStocksForIngredients(array $ingredientIds): array
    {
        $ingredientIds = array_values(array_filter(array_map('intval', $ingredientIds)));
        if (empty($ingredientIds)) {
            return [];
        }

        $balanceExpression = $this->stockBalanceExpression();

        return LichSuKho::query()
            ->whereIn('nguyen_lieu_id', $ingredientIds)
            ->select('nguyen_lieu_id')
            ->selectRaw("COALESCE({$balanceExpression}, 0) as ton_kho")
            ->groupBy('nguyen_lieu_id')
            ->pluck('ton_kho', 'nguyen_lieu_id')
            ->map(fn($value) => (float) $value)
            ->all();
    }
}
