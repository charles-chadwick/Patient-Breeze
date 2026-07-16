<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePatientVitalsRequest;
use App\Models\Patient;
use App\Models\PatientVitals;
use Illuminate\Http\RedirectResponse;

class PatientVitalsController extends Controller
{
    public function store(StorePatientVitalsRequest $request, Patient $patient): RedirectResponse
    {
        $attributes = $request->validated();

        // Whoever charts the readings took them, unless they name a different
        // clinician — the common case is a nurse recording their own rounding.
        $attributes['recorded_by'] ??= $request->user()->id;

        $patient->patientVitals()->create($attributes);

        return redirect()->route('patients.show', $patient)
            ->with('success', __('flash.vitals.added'));
    }

    public function destroy(Patient $patient, PatientVitals $patient_vitals): RedirectResponse
    {
        $patient_vitals->delete();

        return redirect()->route('patients.show', $patient)
            ->with('success', __('flash.vitals.removed'));
    }
}
