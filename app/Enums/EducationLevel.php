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

    /**
     * Level used to compare educations. SMA and SMK are the same level.
     */
    public function rank(): int
    {
        return match ($this) {
            self::Sd => 1,
            self::Smp => 2,
            self::Sma, self::Smk => 3,
            self::D3 => 4,
            self::S1 => 5,
            self::S2 => 6,
        };
    }

    /**
     * Determine if this education is at least the given minimum.
     */
    public function meets(self $minimum): bool
    {
        return $this->rank() >= $minimum->rank();
    }

    /**
     * Label for a minimum requirement, e.g. "Min. SMA/SMK".
     */
    public function minimumLabel(): string
    {
        return 'Min. '.match ($this) {
            self::Sma, self::Smk => 'SMA/SMK',
            default => $this->label(),
        };
    }
}
