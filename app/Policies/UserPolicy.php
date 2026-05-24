<?php

namespace App\Policies;

use App\Models\NguoiDung;
use Illuminate\Support\Facades\Auth;

/**
 * Policy kiểm soát quyền quản lý tài khoản người dùng.
 *
 * Tập trung logic phân quyền được trải rộng ở các private methods trong UserController:
 * actorCanManageAdmins(), canManageRole(), ensureCanManageTargetUser(), ensureUserInCurrentStore()
 */
class UserPolicy
{
    /**
     * Chỉ chủ cửa hàng mới có thể xem/quản lý danh sách admin.
     */
    public function manageAdmins(NguoiDung $actor): bool
    {
        return $actor->vai_tro === 'chủ cửa hàng';
    }

    /**
     * Kiểm tra actor có thể gán vai trò mục tiêu không.
     */
    public function assignRole(NguoiDung $actor, string $targetRole): bool
    {
        $allowedRoles = $this->manageAdmins($actor)
            ? ['khách hàng', 'nhân viên', 'quản lý', 'chủ cửa hàng']
            : ['khách hàng', 'nhân viên'];

        return in_array($targetRole, $allowedRoles, true);
    }

    /**
     * Kiểm tra actor có thể thao tác với user mục tiêu không.
     * Quản lý chỉ được quản lý khách hàng và nhân viên.
     * Chủ cửa hàng được quản lý tất cả.
     */
    public function manageUser(NguoiDung $actor, NguoiDung $targetUser): bool
    {
        if ($this->manageAdmins($actor)) {
            return true;
        }

        return !in_array($targetUser->vai_tro, ['quản lý', 'chủ cửa hàng'], true);
    }

    /**
     * Kiểm tra actor có thể thao tác user trong cùng cửa hàng không.
     */
    public function sameStore(NguoiDung $actor, NguoiDung $targetUser): bool
    {
        $actorStoreId = $this->resolveActorStoreId($actor);

        // Không có store scope → không giới hạn
        if (!$actorStoreId) {
            return true;
        }

        return (int) ($targetUser->cua_hang_id ?? 0) === (int) $actorStoreId;
    }

    /**
     * Kiểm tra actor có thể duyệt tài khoản với vai trò mục tiêu không.
     */
    public function confirmRole(NguoiDung $actor, string $targetRole): bool
    {
        return match ($actor->vai_tro) {
            'chủ cửa hàng' => in_array($targetRole, ['nhân viên', 'quản lý', 'chủ cửa hàng'], true),
            'quản lý'      => $targetRole === 'nhân viên',
            default        => false,
        };
    }

    /**
     * Danh sách vai trò mà actor có thể duyệt.
     */
    public function confirmableRoles(NguoiDung $actor): array
    {
        return match ($actor->vai_tro) {
            'chủ cửa hàng' => ['nhân viên', 'quản lý', 'chủ cửa hàng'],
            'quản lý'      => ['nhân viên'],
            default        => [],
        };
    }

    /**
     * Lấy store ID của actor (từ trực tiếp hoặc qua hồ sơ quản lý).
     */
    private function resolveActorStoreId(NguoiDung $actor): ?int
    {
        return $actor->cua_hang_id
            ?: ($actor->hoSoQuanLy?->cua_hang_id ?? null);
    }
}
