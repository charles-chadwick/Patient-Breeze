<?php

namespace Database\Seeders;

use App\Enums\GenderAtBirth;
use App\Models\LabOrder;
use App\Models\LabReferenceRange;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class LabReferenceRangeSeeder extends Seeder
{
    /**
     * Common reference ranges keyed by lab order name. Each range may be scoped to
     * a gender-at-birth and/or an age band (min/max in whole years, inclusive).
     * Null bounds mean "applies to everyone / any age".
     *
     * @var array<string, list<array{gender?: string, min_age?: int, max_age?: int, low?: string, high?: string, unit: string}>>
     */
    private const RANGES = [
        'Hemoglobin' => [
            ['max_age' => 17, 'low' => '11.0', 'high' => '16.0', 'unit' => 'g/dL'],
            ['gender' => 'Male', 'min_age' => 18, 'low' => '13.5', 'high' => '17.5', 'unit' => 'g/dL'],
            ['gender' => 'Female', 'min_age' => 18, 'low' => '12.0', 'high' => '15.5', 'unit' => 'g/dL'],
        ],
        'Hematocrit' => [
            ['gender' => 'Male', 'min_age' => 18, 'low' => '41.0', 'high' => '50.0', 'unit' => '%'],
            ['gender' => 'Female', 'min_age' => 18, 'low' => '36.0', 'high' => '44.0', 'unit' => '%'],
        ],
        'Platelet Count' => [
            ['low' => '150', 'high' => '400', 'unit' => 'x10^9/L'],
        ],
        'White Blood Cell Count' => [
            ['low' => '4.5', 'high' => '11.0', 'unit' => 'x10^9/L'],
        ],
        'Glucose, Fasting' => [
            ['low' => '70', 'high' => '99', 'unit' => 'mg/dL'],
        ],
        'Hemoglobin A1c' => [
            ['high' => '5.7', 'unit' => '%'],
        ],
        'Creatinine, Serum' => [
            ['gender' => 'Male', 'min_age' => 18, 'low' => '0.74', 'high' => '1.35', 'unit' => 'mg/dL'],
            ['gender' => 'Female', 'min_age' => 18, 'low' => '0.59', 'high' => '1.04', 'unit' => 'mg/dL'],
        ],
        'Sodium, Serum' => [
            ['low' => '136', 'high' => '145', 'unit' => 'mmol/L'],
        ],
        'Potassium, Serum' => [
            ['low' => '3.5', 'high' => '5.1', 'unit' => 'mmol/L'],
        ],
        'Calcium, Serum' => [
            ['low' => '8.6', 'high' => '10.2', 'unit' => 'mg/dL'],
        ],
        'Thyroid Stimulating Hormone (TSH)' => [
            ['low' => '0.4', 'high' => '4.0', 'unit' => 'mIU/L'],
        ],
        'Cholesterol, Total' => [
            ['high' => '200', 'unit' => 'mg/dL'],
        ],
        'HDL Cholesterol' => [
            ['gender' => 'Male', 'low' => '40', 'unit' => 'mg/dL'],
            ['gender' => 'Female', 'low' => '50', 'unit' => 'mg/dL'],
        ],
        'LDL Cholesterol, Direct' => [
            ['high' => '100', 'unit' => 'mg/dL'],
        ],
        'Triglycerides' => [
            ['high' => '150', 'unit' => 'mg/dL'],
        ],
        'Ferritin' => [
            ['gender' => 'Male', 'min_age' => 18, 'low' => '24', 'high' => '336', 'unit' => 'ng/mL'],
            ['gender' => 'Female', 'min_age' => 18, 'low' => '11', 'high' => '307', 'unit' => 'ng/mL'],
        ],
        'Iron, Total' => [
            ['gender' => 'Male', 'min_age' => 18, 'low' => '65', 'high' => '176', 'unit' => 'mcg/dL'],
            ['gender' => 'Female', 'min_age' => 18, 'low' => '50', 'high' => '170', 'unit' => 'mcg/dL'],
        ],
        'Vitamin D, 25-Hydroxy' => [
            ['low' => '30', 'high' => '100', 'unit' => 'ng/mL'],
        ],
        'Prostate Specific Antigen (PSA), Total' => [
            ['gender' => 'Male', 'high' => '4.0', 'unit' => 'ng/mL'],
        ],
        'Alanine Aminotransferase (ALT)' => [
            ['gender' => 'Male', 'low' => '7', 'high' => '55', 'unit' => 'U/L'],
            ['gender' => 'Female', 'low' => '7', 'high' => '45', 'unit' => 'U/L'],
        ],
    ];

    /**
     * Seed reference ranges for the common analytes above.
     *
     * Runs after LabOrderSeeder so the target lab orders already exist.
     */
    public function run(): void
    {
        $now = Carbon::now();
        $lab_orders_by_name = LabOrder::query()->pluck('id', 'name');

        foreach (self::RANGES as $lab_order_name => $ranges) {
            $lab_order_id = $lab_orders_by_name->get($lab_order_name);

            if ($lab_order_id === null) {
                continue;
            }

            foreach ($ranges as $range) {
                LabReferenceRange::create([
                    'lab_order_id' => $lab_order_id,
                    'gender_at_birth' => isset($range['gender']) ? GenderAtBirth::from($range['gender']) : null,
                    'min_age' => $range['min_age'] ?? null,
                    'max_age' => $range['max_age'] ?? null,
                    'low_value' => $range['low'] ?? null,
                    'high_value' => $range['high'] ?? null,
                    'unit' => $range['unit'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
}
