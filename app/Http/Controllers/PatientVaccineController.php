<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePatientVaccineRequest;
use App\Models\Patient;
use App\Models\PatientVaccine;
use Illuminate\Http\RedirectResponse;

class PatientVaccineController extends Controller
{
    public function store(StorePatientVaccineRequest $request, Patient $patient): RedirectResponse
    {
        $attributes = $request->validated();

        // Whoever records the dose gave it, unless they name a different
        // clinician — the common case is a nurse charting their own shot.
        $attributes['administered_by'] ??= $request->user()->id;

        $patient->patientVaccines()->create($attributes);

        return redirect()->route('patients.show', $patient)
            ->with('success', __('flash.vaccines.added'));
    }

    public function destroy(Patient $patient, PatientVaccine $patient_vaccine): RedirectResponse
    {
        $patient_vaccine->delete();

        return redirect()->route('patients.show', $patient)
            ->with('success', __('flash.vaccines.removed'));
    }
}
