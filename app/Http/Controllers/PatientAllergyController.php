<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePatientAllergyRequest;
use App\Models\Patient;
use App\Models\PatientAllergy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PatientAllergyController extends Controller
{
    public function store(StorePatientAllergyRequest $request, Patient $patient): RedirectResponse
    {
        $patient->patientAllergies()->create($request->validated());

        // Recording an allergy is itself a review of the allergy list, so the
        // chart never shows a populated list alongside "not yet reviewed".
        $patient->markAllergiesReviewedBy($request->user());

        return redirect()->route('patients.show', $patient)
            ->with('success', __('flash.allergies.added'));
    }

    public function destroy(Patient $patient, PatientAllergy $patient_allergy): RedirectResponse
    {
        $patient_allergy->delete();

        return redirect()->route('patients.show', $patient)
            ->with('success', __('flash.allergies.removed'));
    }

    /**
     * Stamp the patient's allergy list as reviewed. With an empty list this is
     * what records "no known allergies" as a deliberate clinical finding rather
     * than an unanswered question.
     */
    public function review(Request $request, Patient $patient): RedirectResponse
    {
        $patient->markAllergiesReviewedBy($request->user());

        return redirect()->route('patients.show', $patient)
            ->with('success', __('flash.allergies.reviewed'));
    }
}
