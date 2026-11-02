<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case Worker = 'worker';
    case Lpk = 'lpk';
    case Company = 'company';

    /**
     * Get the human readable label for the role.
     */
    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::Worker => 'Pencari Kerja',
            self::Lpk => 'LPK',
            self::Company => 'Perusahaan',
        };
    }
}
