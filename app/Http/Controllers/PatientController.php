<?php

namespace App\Http\Controllers;

use App\Actions\ManageAvatarAction;
use App\Enums\BloodType;
use App\Enums\GenderAtBirth;
use App\Enums\GenderIdentity;
use App\Enums\UserRole;
use App\Http\Requests\StorePatientRequest;
use App\Http\Requests\UpdatePatientRequest;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PatientController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim();
        $sort_by = $request->string('sort_by', 'last_name')->toString();
        $direction = $request->input('direction') === 'desc' ? 'desc' : 'asc';

        $patients = Patient::with(['user', 'user.media'])
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

        $patient = DB::transaction(function () use ($request, $validated, $avatarAction) {
            $user = User::create(array_merge(User::identityData($validated), [
                'password' => Hash::make(Str::random(32)),
            ]));

            $user->assignRole(UserRole::Patient->value);
            $avatarAction->execute($user, $request->file('avatar'), false);

            return Patient::create([
                'user_id' => $user->id,
                'mrn' => Patient::generateMrn(),
                'date_of_birth' => $validated['date_of_birth'],
                'gender_at_birth' => $validated['gender_at_birth'],
                'gender_identity' => $validated['gender_identity'] ?? null,
                'blood_type' => $validated['blood_type'] ?? null,
            ]);
        });

        return redirect()->route('patients.show', $patient);
    }

    public function show(Patient $patient): Response
    {
        $patient->load([
            'user',
            'user.media',
            'appointments' => fn ($query) => $query->orderBy('date', 'desc')->limit(50),
            'appointments.users',
            'appointments.users.media',
        ]);

        return Inertia::render('Patients/Show', [
            'patient' => $patient,
        ]);
    }

    public function edit(Patient $patient): Response
    {
        $patient->load(['user', 'user.media']);

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

        $patient->user->update(User::identityData($validated));

        $patient->update([
            'date_of_birth' => $validated['date_of_birth'],
            'gender_at_birth' => $validated['gender_at_birth'],
            'gender_identity' => $validated['gender_identity'] ?? null,
            'blood_type' => $validated['blood_type'] ?? null,
        ]);

        $avatarAction->execute($patient->user, $request->file('avatar'), (bool) ($validated['remove_avatar'] ?? false));

        return redirect()->route('patients.show', $patient);
    }
}
