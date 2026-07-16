<?php

namespace App\Enums;

/**
 * The measurable readings that make up a vitals set. Doubles as the row
 * definition for the flowsheet and the single source of truth for the adult
 * normal ranges used to flag out-of-range values.
 *
 * Ranges are expressed in the canonical stored units (°C, kg, cm). Paediatric
 * and age-adjusted ranges are intentionally out of scope for now.
 */
enum VitalType: string
{
    case Systolic = 'Systolic';
    case Diastolic = 'Diastolic';
    case HeartRate = 'Heart Rate';
    case RespiratoryRate = 'Respiratory Rate';
    case Temperature = 'Temperature';
    case OxygenSaturation = 'Oxygen Saturation';
    case Weight = 'Weight';
    case Height = 'Height';
    case PainScore = 'Pain Score';

    /**
     * The `patient_vitals` column this reading is stored in.
     */
    public function column(): string
    {
        return match ($this) {
            self::Systolic => 'systolic',
            self::Diastolic => 'diastolic',
            self::HeartRate => 'heart_rate',
            self::RespiratoryRate => 'respiratory_rate',
            self::Temperature => 'temperature',
            self::OxygenSaturation => 'oxygen_saturation',
            self::Weight => 'weight',
            self::Height => 'height',
            self::PainScore => 'pain_score',
        };
    }

    /**
     * Human-facing, translatable label. The backing value stays as stored data.
     */
    public function label(): string
    {
        return __('enums.vital_type.'.$this->value);
    }

    /**
     * The canonical unit a reading of this type is measured in, or null when it
     * is a bare score.
     */
    public function unit(): ?string
    {
        return match ($this) {
            self::Systolic, self::Diastolic => 'mmHg',
            self::HeartRate => 'bpm',
            self::RespiratoryRate => 'breaths/min',
            self::Temperature => '°C',
            self::OxygenSaturation => '%',
            self::Weight => 'kg',
            self::Height => 'cm',
            self::PainScore => null,
        };
    }

    /**
     * The inclusive adult normal range as `[low, high]`, or null for readings
     * that are not range-flagged (e.g. weight, height, pain score).
     *
     * @return array{0: float, 1: float}|null
     */
    public function normalRange(): ?array
    {
        return match ($this) {
            self::Systolic => [90.0, 140.0],
            self::Diastolic => [60.0, 90.0],
            self::HeartRate => [60.0, 100.0],
            self::RespiratoryRate => [12.0, 20.0],
            self::Temperature => [36.1, 38.0],
            self::OxygenSaturation => [95.0, 100.0],
            self::Weight, self::Height, self::PainScore => null,
        };
    }

    /**
     * Whether the given reading falls outside this type's normal range. A null
     * value (not recorded) and an unranged type are never abnormal.
     */
    public function isAbnormal(int|float|null $value): bool
    {
        $range = $this->normalRange();

        if ($value === null || $range === null) {
            return false;
        }

        [$low, $high] = $range;

        return $value < $low || $value > $high;
    }

    /**
     * The types that make up a flowsheet, in display order.
     *
     * @return list<self>
     */
    public static function flowsheetOrder(): array
    {
        return self::cases();
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
