<?php

namespace App\Enums;

/**
 * Who receives the money of a payment and verifies its transfer proof.
 */
enum PaymentCollector: string
{
    /**
     * Paid to Careja (Midtrans QRIS or Careja's account), verified by the
     * super admin. Careja hands the LPK's part over after the agreed cut.
     */
    case Careja = 'careja';

    /**
     * Transferred straight to the LPK's own bank account and verified by the LPK.
     */
    case Lpk = 'lpk';

    public function label(): string
    {
        return match ($this) {
            self::Careja => 'Melalui Careja',
            self::Lpk => 'Langsung ke rekening LPK',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Careja => 'QRIS otomatis (Midtrans Careja) atau transfer ke Careja, diverifikasi admin Careja. Careja menyerahkan dana ke LPK setelah dipotong bagian Careja.',
            self::Lpk => 'Pencari kerja transfer ke rekening LPK dan mengunggah bukti, lalu LPK sendiri yang memverifikasi.',
        };
    }
}
