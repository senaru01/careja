<?php

namespace App\Enums;

enum OrganizationType: string
{
    case Lpk = 'lpk';
    case Company = 'company';

    public function label(): string
    {
        return match ($this) {
            self::Lpk => 'LPK',
            self::Company => 'Perusahaan',
        };
    }

    /**
     * The role given to user accounts that belong to this organization type.
     */
    public function userRole(): UserRole
    {
        return match ($this) {
            self::Lpk => UserRole::Lpk,
            self::Company => UserRole::Company,
        };
    }
}
