<?php

namespace App\Enums;

enum UserRole: string
{
    case KHACH_HANG = 'khách hàng';
    case NHAN_VIEN = 'nhân viên';
    case QUAN_LY = 'quản lý';
    case CHU_CUA_HANG = 'chủ cửa hàng';

    /**
     * Các vai trò quản trị (nhân viên, quản lý, chủ cửa hàng).
     */
    public static function staffRoles(): array
    {
        return [
            self::NHAN_VIEN,
            self::QUAN_LY,
            self::CHU_CUA_HANG,
        ];
    }

    /**
     * Các giá trị vai trò quản trị dạng string.
     */
    public static function staffRoleValues(): array
    {
        return array_map(fn(self $role) => $role->value, self::staffRoles());
    }

    /**
     * Các vai trò có quyền quản lý cửa hàng.
     */
    public static function managerRoles(): array
    {
        return [self::QUAN_LY, self::CHU_CUA_HANG];
    }

    /**
     * Các giá trị vai trò quản lý dạng string.
     */
    public static function managerRoleValues(): array
    {
        return array_map(fn(self $role) => $role->value, self::managerRoles());
    }

    /**
     * Kiểm tra vai trò có thuộc nhóm quản trị không.
     */
    public function isStaff(): bool
    {
        return in_array($this, self::staffRoles(), true);
    }

    /**
     * Kiểm tra vai trò có thuộc nhóm quản lý không.
     */
    public function isManager(): bool
    {
        return in_array($this, self::managerRoles(), true);
    }

    /**
     * Chuẩn hóa chuỗi vai trò từ nhiều dạng input.
     */
    public static function normalize(?string $role): ?self
    {
        if (!$role) {
            return null;
        }

        $normalized = mb_strtolower(trim($role));

        return match ($normalized) {
            'khach hang', 'khách hàng', 'customer' => self::KHACH_HANG,
            'nhan vien', 'nhân viên', 'staff' => self::NHAN_VIEN,
            'quan ly', 'quản lý', 'admin', 'administrator' => self::QUAN_LY,
            'chu cua hang', 'chủ cửa hàng', 'owner', 'store owner' => self::CHU_CUA_HANG,
            default => null,
        };
    }

    public function label(): string
    {
        return $this->value;
    }
}
