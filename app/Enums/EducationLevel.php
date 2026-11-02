<?php

namespace App\Enums;

enum EducationLevel: string
{
    case Sd = 'sd';
    case Smp = 'smp';
    case Sma = 'sma';
    case Smk = 'smk';
    case D3 = 'd3';
    case S1 = 's1';
    case S2 = 's2';

    public function label(): string
    {
        return match ($this) {
            self::Sd => 'SD',
            self::Smp => 'SMP',
            self::Sma => 'SMA',
            self::Smk => 'SMK',
            self::D3 => 'D3',
            self::S1 => 'S1',
            self::S2 => 'S2',
        };
    }
}
