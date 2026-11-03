<?php

namespace Tests\Feature;

use Tests\TestCase;

class PaymentCatalogTest extends TestCase
{
    public function test_landing_page_lists_every_payment_with_its_configured_price(): void
    {
        config([
            'careja.registration_fee' => 50000,
            'careja.exam_registration_fee' => 6500,
            'careja.interview_fee' => 500000,
            'careja.medical_checkup_fee' => 600000,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSeeInOrder([
                'Katalog Pembayaran',
                'Keanggotaan Pencari Kerja', 'Rp50.000',
                'Biaya Ikut Sesi Tes', 'Rp6.500',
                'Pengambilan Kandidat Wawancara', 'Rp500.000',
                'Medical Checkup', 'Rp600.000',
            ]);
    }
}
