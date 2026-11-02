<?php

namespace App\Enums;

enum SkillLevel: string
{
    case Beginner = 'beginner';
    case Intermediate = 'intermediate';
    case Advanced = 'advanced';

    public function label(): string
    {
        return match ($this) {
            self::Beginner => 'Dasar',
            self::Intermediate => 'Menengah',
            self::Advanced => 'Mahir',
        };
    }
}
