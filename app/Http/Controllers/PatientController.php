<?php

namespace App\Http\Controllers;

use App\Actions\ManageAvatarAction;
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
        $search = $request->string('search')->trim();
        $sort_by = $request->string('sort_by', 'last_name')->toString();
        $direction = $request->input('direction') === 'desc' ? 'desc' : 'asc';

        $patients = Patient::with('media')
            ->when($search, fn ($query) => $query->search($search))
            ->sort($sort_by, $direction)
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Patients/Index', [
            'patients' => $patients,
            'search' => $search->toString(),
            'sort_by' => $sort_by,
            'direction' => $direction,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Patients/Form', [
            'gender_at_birth_options' => array_column(GenderAtBirth::cases(), 'value'),
            'gender_identity_options' => array_column(GenderIdentity::cases(), 'value'),
            'blood_type_options' => array_column(BloodType::cases(), 'value'),
        ]);
    }

    public function store(StorePatientRequest $request, ManageAvatarAction $avatarAction): RedirectResponse
    {
        $validated = $request->validated();

        $patient = Patient::create([
            'prefix' => $validated['prefix'] ?? '',
            'first_name' => $validated['first_name'],
            'middle_name' => $validated['middle_name'] ?? '',
            'last_name' => $validated['last_name'],
            'suffix' => $validated['suffix'] ?? '',
            'email' => $validated['email'],
            'mrn' => Patient::generateMrn(),
            'date_of_birth' => $validated['date_of_birth'],
            'gender_at_birth' => $validated['gender_at_birth'],
            'gender_identity' => $validated['gender_identity'] ?? null,
            'blood_type' => $validated['blood_type'] ?? null,
        ]);

        $avatarAction->execute($patient, $request->file('avatar'), false);

        return redirect()->route('patients.show', $patient);
    }

    public function show(Patient $patient, Request $request): Response
    {
        $search = $request->string('search')->trim();

        $patient->load('media');

        $appointments = $patient->appointments()
            ->with(['users.media'])
            ->when($search, fn ($query) => $query->where(fn ($q) => $q
                ->where('reason', 'like', "%{$search}%")
                ->orWhere('notes', 'like', "%{$search}%")
            ))
            ->orderBy('date', 'desc')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Patients/Show', [
            'patient' => $patient,
            'appointments' => $appointments,
            'appointment_search' => $search->toString(),
        ]);
    }

    public function edit(Patient $patient): Response
    {
        $patient->load('media');

        return Inertia::render('Patients/Form', [
            'patient' => $patient,
            'gender_at_birth_options' => array_column(GenderAtBirth::cases(), 'value'),
            'gender_identity_options' => array_column(GenderIdentity::cases(), 'value'),
            'blood_type_options' => array_column(BloodType::cases(), 'value'),
        ]);
    }

    public function update(UpdatePatientRequest $request, Patient $patient, ManageAvatarAction $avatarAction): RedirectResponse
    {
        $validated = $request->validated();

        $patient->update([
            'prefix' => $validated['prefix'] ?? '',
            'first_name' => $validated['first_name'],
            'middle_name' => $validated['middle_name'] ?? '',
            'last_name' => $validated['last_name'],
            'suffix' => $validated['suffix'] ?? '',
            'email' => $validated['email'],
            'date_of_birth' => $validated['date_of_birth'],
            'gender_at_birth' => $validated['gender_at_birth'],
            'gender_identity' => $validated['gender_identity'] ?? null,
            'blood_type' => $validated['blood_type'] ?? null,
        ]);

        $avatarAction->execute($patient, $request->file('avatar'), (bool) ($validated['remove_avatar'] ?? false));

        return redirect()->route('patients.show', $patient);
    }
}
