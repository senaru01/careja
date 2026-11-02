<?php

namespace App\Enums;

enum MedicalStatus: string
{
    case Fit = 'fit';
    case Unfit = 'unfit';

    public function label(): string
    {
        return match ($this) {
            self::Fit => 'Sehat (Fit)',
            self::Unfit => 'Tidak Sehat (Unfit)',
        };
    }
}
