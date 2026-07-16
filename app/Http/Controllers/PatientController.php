<?php

namespace App\Http\Controllers;

use App\Actions\BuildPatientChartAction;
use App\Actions\CreatePatientAction;
use App\Actions\UpdatePatientAction;
use App\Enums\BloodType;
use App\Enums\GenderAtBirth;
use App\Enums\GenderIdentity;
use App\Http\Requests\StorePatientRequest;
use App\Http\Requests\UpdatePatientRequest;
use App\Models\Patient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PatientController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Patient::class);

        return Inertia::render('Patients/Index', Patient::listing($request));
    }

    public function create(): Response
    {
        $this->authorize('create', Patient::class);

        return Inertia::render('Patients/Form', [
            'gender_at_birth_options' => array_column(GenderAtBirth::cases(), 'value'),
            'gender_identity_options' => array_column(GenderIdentity::cases(), 'value'),
            'blood_type_options' => array_column(BloodType::cases(), 'value'),
        ]);
    }

    public function store(StorePatientRequest $request, CreatePatientAction $createPatient): RedirectResponse
    {
        $this->authorize('create', Patient::class);

        $patient = $createPatient->execute($request->validated(), $request->file('avatar'));

        return redirect()->route('patients.show', $patient)
            ->with('success', __('flash.patients.created'));
    }

    public function show(Patient $patient, Request $request, BuildPatientChartAction $buildChart): Response
    {
        $this->authorize('view', $patient);

        return Inertia::render('Patients/Show', $buildChart->execute($patient, $request));
    }

    public function edit(Patient $patient): Response
    {
        $this->authorize('update', $patient);

        $patient->load('media');

        return Inertia::render('Patients/Form', [
            'patient' => $patient,
            'gender_at_birth_options' => array_column(GenderAtBirth::cases(), 'value'),
            'gender_identity_options' => array_column(GenderIdentity::cases(), 'value'),
            'blood_type_options' => array_column(BloodType::cases(), 'value'),
        ]);
    }

    public function update(UpdatePatientRequest $request, Patient $patient, UpdatePatientAction $updatePatient): RedirectResponse
    {
        $this->authorize('update', $patient);

        $updatePatient->execute($patient, $request->validated(), $request->file('avatar'));

        return redirect()->route('patients.show', $patient)
            ->with('success', __('flash.patients.updated'));
    }

    public function destroy(Patient $patient): RedirectResponse
    {
        $this->authorize('delete', $patient);

        $patient->delete();

        return redirect()->route('patients.index')
            ->with('success', __('flash.patients.deleted'));
    }
}
