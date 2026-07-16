<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePatientInsuranceRequest;
use App\Models\Patient;
use App\Models\PatientInsurance;
use Illuminate\Http\RedirectResponse;

class PatientInsuranceController extends Controller
{
    public function store(StorePatientInsuranceRequest $request, Patient $patient): RedirectResponse
    {
        $patient->patientInsurances()->create($request->validated());

        return redirect()->route('patients.show', $patient)
            ->with('success', __('flash.patient_insurances.added'));
    }

    public function destroy(Patient $patient, PatientInsurance $patient_insurance): RedirectResponse
    {
        $patient_insurance->delete();

        return redirect()->route('patients.show', $patient)
            ->with('success', __('flash.patient_insurances.removed'));
    }
}
