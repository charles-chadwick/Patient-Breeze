<?php

namespace App\Enums;

enum InsurancePlanType: string
{
    case Ppo = 'PPO';
    case Hmo = 'HMO';
    case Epo = 'EPO';
    case Pos = 'POS';
    case Hdhp = 'HDHP';
    case Medicare = 'Medicare';
    case Medicaid = 'Medicaid';
    case Other = 'Other';

    /**
     * Human-facing, translatable label. The backing value stays as stored data.
     */
    public function label(): string
    {
        return __('enums.insurance_plan_type.'.$this->value);
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
