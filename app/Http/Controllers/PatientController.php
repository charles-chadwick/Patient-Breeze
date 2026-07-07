<?php

namespace App\Http\Controllers;

use App\Actions\ManageAvatarAction;
use App\Enums\BloodType;
use App\Enums\ContactType;
use App\Enums\DiscussionType;
use App\Enums\GenderAtBirth;
use App\Enums\GenderIdentity;
use App\Http\Requests\StorePatientRequest;
use App\Http\Requests\UpdatePatientRequest;
use App\Models\Patient;
use App\Models\User;
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

    public function store(StorePatientRequest $request, ManageAvatarAction $avatarAction): RedirectResponse
    {
        $this->authorize('create', Patient::class);

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
        $this->authorize('view', $patient);

        $search = $request->string('search')->trim()->toString();

        $patient->load([
            'media',
            'contacts' => fn ($query) => $query->orderBy('name'),
        ]);

        $appointments = $patient->appointments()
            ->with(['users.media'])
            ->when($search, fn ($query) => $query->matchingReasonOrNotes($search))
            ->orderBy('date', 'desc')
            ->paginate(10)
            ->withQueryString();

        $users = User::select('id', 'first_name', 'last_name', 'email')
            ->with(['media' => fn ($query) => $query->where('collection_name', 'avatar')])
            ->orderBy('last_name')
            ->get()
            ->map(fn ($user) => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'avatar_url' => $user->avatar_url,
            ]);

        return Inertia::render('Patients/Show', [
            'patient' => $patient,
            'appointments' => $appointments,
            'appointment_search' => $search,
            'contact_types' => ContactType::values(),
            'contactable_type' => Patient::class,
            'discussion_types' => DiscussionType::values(),
            'users' => $users,
            'discussions' => Inertia::defer(fn () => $patient->discussions()
                ->with([
                    'participants.participantable.media',
                    'posts' => fn ($query) => $query->with('user.media')
                        ->orderBy('created_at'),
                ])
                ->latest()
                ->get()
            ),
        ]);
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

    public function update(UpdatePatientRequest $request, Patient $patient, ManageAvatarAction $avatarAction): RedirectResponse
    {
        $this->authorize('update', $patient);

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
