<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePatientDiagnosisRequest;
use App\Models\Patient;
use App\Models\PatientDiagnosis;
use Illuminate\Http\RedirectResponse;

class PatientDiagnosisController extends Controller
{
    public function store(StorePatientDiagnosisRequest $request, Patient $patient): RedirectResponse
    {
        $patient->patientDiagnoses()->create($request->validated());

        return redirect()->route('patients.show', $patient)
            ->with('success', __('flash.diagnoses.added'));
    }

    public function destroy(Patient $patient, PatientDiagnosis $patient_diagnosis): RedirectResponse
    {
        $patient_diagnosis->delete();

        return redirect()->route('patients.show', $patient)
            ->with('success', __('flash.diagnoses.removed'));
    }
}
