<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePatientMedicationRequest;
use App\Models\Patient;
use App\Models\PatientMedication;
use Illuminate\Http\RedirectResponse;

class PatientMedicationController extends Controller
{
    public function store(StorePatientMedicationRequest $request, Patient $patient): RedirectResponse
    {
        $patient->patientMedications()->create($request->validated());

        return redirect()->route('patients.show', $patient)
            ->with('success', __('flash.medications.added'));
    }

    public function destroy(Patient $patient, PatientMedication $patient_medication): RedirectResponse
    {
        $patient_medication->delete();

        return redirect()->route('patients.show', $patient)
            ->with('success', __('flash.medications.removed'));
    }
}
