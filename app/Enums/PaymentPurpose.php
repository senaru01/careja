<?php

namespace App\Enums;

enum PaymentPurpose: string
{
    case Membership = 'membership';
    case ExamSession = 'exam_session';
    case ExamRegistration = 'exam_registration';

    public function label(): string
    {
        return match ($this) {
            self::Membership => 'Keanggotaan',
            self::ExamSession => 'Penerbitan Sesi Tes',
            self::ExamRegistration => 'Pendaftaran Tes',
        };
    }
}
