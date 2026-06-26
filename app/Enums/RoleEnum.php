<?php

declare(strict_types=1);

namespace App\Enums;

enum RoleEnum: string
{
    case SuperAdmin = 'Super Admin';
    case Director = 'Giám đốc';
    case IT = 'IT';
    case Sales = 'Phòng Kinh doanh';
    case Consultant = 'Tư vấn';
    case Accountant = 'Kế toán';

    /**
     * @return list<string>
     */
    public static function systemRoleNames(): array
    {
        return array_map(
            static fn (self $role): string => $role->value,
            self::cases(),
        );
    }

    public static function isSystemRole(string $roleName): bool
    {
        return in_array($roleName, self::systemRoleNames(), true);
    }
}
