<?php

namespace App\Enums;

/**
 * Where a test session is in its life cycle. Derived from the publish date
 * and the schedule, never stored.
 */
enum ExamPhase: string
{
    case Draft = 'draft';
    case Open = 'open';
    case Running = 'running';
    case Finished = 'finished';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draf (belum terbit)',
            self::Open => 'Pendaftaran Dibuka',
            self::Running => 'Tes Berlangsung',
            self::Finished => 'Selesai',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Open => 'blue',
            self::Running => 'yellow',
            self::Finished => 'green',
        };
    }
}
