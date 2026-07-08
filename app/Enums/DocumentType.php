<?php

namespace App\Enums;

enum DocumentType: string
{
    case LabResult = 'LabResult';
    case Insurance = 'Insurance';
    case Referral = 'Referral';
    case Consent = 'Consent';
    case Prescription = 'Prescription';
    case Identification = 'Identification';
    case Certification = 'Certification';
    case Note = 'Note';
    case Other = 'Other';

    public function label(): string
    {
        return __('enums.document_type.'.$this->value);
    }

    /** @return string[] */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
