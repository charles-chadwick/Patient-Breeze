<?php

namespace App\Actions;

use App\Models\LabOrder;
use App\Models\Patient;
use App\Models\PatientLabResult;

class RecordPatientLabResultAction
{
    /**
     * Record a lab result for a patient, snapshotting the catalog test details
     * and the reference range that applied to the patient at collection time.
     *
     * @param  array<string, mixed>  $validated
     */
    public function execute(Patient $patient, array $validated): PatientLabResult
    {
        $lab_order = LabOrder::findOrFail($validated['lab_order_id']);
        $range = $lab_order->resolveReferenceRangeFor($patient);

        /** @var PatientLabResult $result */
        $result = $patient->patientLabResults()->create([
            'lab_order_id' => $lab_order->id,
            'name' => $lab_order->name,
            'performing_lab' => $lab_order->performing_lab,
            'cpt_code' => $lab_order->cpt_code,
            'value' => $validated['value'],
            'unit' => $validated['unit'] ?? $range?->unit,
            // Snapshot the exact stored strings so representations like "12.0" survive.
            'reference_low' => $range?->getRawOriginal('low_value'),
            'reference_high' => $range?->getRawOriginal('high_value'),
            'reference_gender' => $range?->gender_at_birth?->value,
            'reference_age' => $patient->currentAge(),
            'collected_at' => $validated['collected_at'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return $result;
    }
}
