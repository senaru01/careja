<?php

namespace App\Enums;

enum DocumentType: string
{
    case Cv = 'cv';
    case IdCard = 'id_card';
    case Diploma = 'diploma';
    case Certificate = 'certificate';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Cv => 'CV',
            self::IdCard => 'KTP',
            self::Diploma => 'Ijazah',
            self::Certificate => 'Sertifikat',
            self::Other => 'Lainnya',
        };
    }
}
