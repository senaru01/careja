<?php

namespace App\Enums;

use App\Models\Organization;

enum PaymentPurpose: string
{
    case Membership = 'membership';
    case ExamSession = 'exam_session';
    case ExamRegistration = 'exam_registration';
    case InterviewAccess = 'interview_access';
    case MedicalCheckup = 'medical_checkup';

    public function label(): string
    {
        return match ($this) {
            self::Membership => 'Keanggotaan',
            self::ExamSession => 'Penerbitan Sesi Tes',
            self::ExamRegistration => 'Pendaftaran Tes',
            self::InterviewAccess => 'Pengambilan Kandidat Wawancara',
            self::MedicalCheckup => 'Medical Checkup',
        };
    }

    /**
     * Price in Rupiah: the LPK's own price when it set one, otherwise the default.
     */
    public function fee(?Organization $lpk = null): int
    {
        return $lpk ? $lpk->feeFor($this) : $this->defaultFee();
    }

    /**
     * Default price in Rupiah from config/careja.php, as listed in the payment catalog.
     */
    public function defaultFee(): int
    {
        return (int) config(match ($this) {
            self::Membership => 'careja.registration_fee',
            self::ExamSession => 'careja.exam_session_fee',
            self::ExamRegistration => 'careja.exam_registration_fee',
            self::InterviewAccess => 'careja.interview_fee',
            self::MedicalCheckup => 'careja.medical_checkup_fee',
        });
    }
}
