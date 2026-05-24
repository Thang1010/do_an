<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case TIEN_MAT = 'tiền mặt';
    case CHUYEN_KHOAN = 'chuyển khoản';

    /**
     * Chuẩn hóa chuỗi phương thức thanh toán từ nhiều dạng input.
     */
    public static function normalize(?string $method): ?self
    {
        if ($method === null || $method === '') {
            return null;
        }

        $method = trim($method);

        return match ($method) {
            'tien_mat', 'tiền mặt' => self::TIEN_MAT,
            'chuyen_khoan', 'chuyển khoản' => self::CHUYEN_KHOAN,
            default => null,
        };
    }

    /**
     * Kiểm tra giá trị có hợp lệ không.
     */
    public static function isValid(string $value): bool
    {
        return in_array($value, array_column(self::cases(), 'value'), true);
    }

    public function label(): string
    {
        return $this->value;
    }
}
