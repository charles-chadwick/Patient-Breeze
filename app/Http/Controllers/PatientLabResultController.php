<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePatientLabResultRequest;
use App\Models\LabOrder;
use App\Models\Patient;
use App\Models\PatientLabResult;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PatientLabResultController extends Controller
{
    public function store(StorePatientLabResultRequest $request, Patient $patient): RedirectResponse
    {
        $validated = $request->validated();

        $labOrder = LabOrder::findOrFail($validated['lab_order_id']);
        $range = $labOrder->resolveReferenceRangeFor($patient);

        $patient->patientLabResults()->create([
            'lab_order_id' => $labOrder->id,
            'name' => $labOrder->name,
            'performing_lab' => $labOrder->performing_lab,
            'cpt_code' => $labOrder->cpt_code,
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

        return redirect()->route('patients.show', $patient)
            ->with('success', __('flash.lab_results.added'));
    }

    public function destroy(Patient $patient, PatientLabResult $patient_lab_result): RedirectResponse
    {
        $patient_lab_result->delete();

        return redirect()->route('patients.show', $patient)
            ->with('success', __('flash.lab_results.removed'));
    }

    /**
     * Resolve the reference range that applies to this patient for a given test,
     * so the "add result" modal can preview the normal/abnormal range live.
     */
    public function referenceRange(Request $request, Patient $patient): JsonResponse
    {
        $validated = $request->validate([
            'lab_order_id' => ['required', 'integer', 'exists:lab_orders,id'],
        ]);

        $labOrder = LabOrder::findOrFail($validated['lab_order_id']);
        $range = $labOrder->resolveReferenceRangeFor($patient);

        return response()->json([
            'reference_range' => $range === null ? null : [
                'low' => $range->low_value,
                'high' => $range->high_value,
                'unit' => $range->unit,
                'gender' => $range->gender_at_birth?->value,
                'label' => $range->label(),
            ],
            'gender' => $patient->gender_at_birth?->value,
            'age' => $patient->currentAge(),
        ]);
    }
}
