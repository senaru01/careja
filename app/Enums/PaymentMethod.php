<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Manual = 'manual';
    case Midtrans = 'midtrans';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Upload bukti',
            self::Midtrans => 'QRIS otomatis',
        };
    }
}
