<?php

namespace App\Http\Controllers;

use App\Actions\CreatePatientAction;
use App\Actions\UpdatePatientAction;
use App\Enums\AppointmentRole;
use App\Enums\AppointmentStatus;
use App\Enums\BloodType;
use App\Enums\ContactType;
use App\Enums\DiscussionType;
use App\Enums\DocumentType;
use App\Enums\DoseForm;
use App\Enums\EncounterNoteType;
use App\Enums\Frequency;
use App\Enums\GenderAtBirth;
use App\Enums\GenderIdentity;
use App\Enums\NoteType;
use App\Http\Requests\StorePatientRequest;
use App\Http\Requests\UpdatePatientRequest;
use App\Models\Document;
use App\Models\EncounterNote;
use App\Models\Patient;
use App\Models\PatientMedication;
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

    public function store(StorePatientRequest $request, CreatePatientAction $createPatient): RedirectResponse
    {
        $this->authorize('create', Patient::class);

        $patient = $createPatient->execute($request->validated(), $request->file('avatar'));

        return redirect()->route('patients.show', $patient)
            ->with('success', __('flash.patients.created'));
    }

    public function show(Patient $patient, Request $request): Response
    {
        $this->authorize('view', $patient);

        $user = $request->user();

        $search = $request->string('search')->trim()->toString();

        $patient->load([
            'media',
            'contacts' => fn ($query) => $query->orderBy('name'),
            'documents' => fn ($query) => $query->with(['media', 'uploader'])->latest(),
            'patientMedications' => fn ($query) => $query->latest(),
        ]);

        $documents = $patient->documents->map(fn (Document $document) => [
            'id' => $document->id,
            'type' => $document->type->value,
            'type_label' => $document->type->label(),
            'name' => $document->name,
            'document_date' => $document->document_date?->toDateString(),
            'notes' => $document->notes,
            'uploaded_by' => $this->uploaderName($document),
            'created_at' => $document->created_at->toDateString(),
            'download_url' => route('patients.documents.download', [$patient->id, $document->id]),
        ]);

        $medications = $patient->patientMedications->map(fn (PatientMedication $medication) => [
            'id' => $medication->id,
            'type' => $medication->type,
            'name' => $medication->name,
            'dosage' => $medication->dosage,
            'dose_form' => $medication->dose_form->value,
            'dose_form_label' => $medication->dose_form->label(),
            'frequency' => $medication->frequency->value,
            'frequency_label' => $medication->frequency->label(),
            'amount' => $medication->amount,
            'ndc' => $medication->ndc,
            'created_at' => $medication->created_at->toDateString(),
        ]);

        return Inertia::render('Patients/Show', [
            'patient' => $patient,
            'appointments' => $patient->paginatedAppointments($search),
            'appointment_search' => $search,
            'status_options' => array_column(AppointmentStatus::cases(), 'value'),
            'role_options' => array_column(AppointmentRole::cases(), 'value'),
            'documents' => $documents,
            'document_type_options' => DocumentType::values(),
            'medications' => $medications,
            'dose_form_options' => DoseForm::values(),
            'frequency_options' => Frequency::values(),
            'contact_types' => ContactType::values(),
            'contactable_type' => Patient::class,
            'discussion_types' => DiscussionType::values(),
            'discussions' => Inertia::defer(fn () => $patient->discussionThread()),
            'note_types' => NoteType::values(),
            'notes' => Inertia::defer(fn () => $patient->notes()->latest()->get()),
            'encounter_note_types' => EncounterNoteType::values(),
            'owner_options' => User::orderBy('first_name')->orderBy('last_name')
                ->get(['id', 'first_name', 'last_name'])
                ->map(fn (User $owner) => [
                    'id' => $owner->id,
                    'name' => trim("{$owner->first_name} {$owner->last_name}"),
                ]),
            'patient_appointments' => $patient->appointments()
                ->orderBy('date', 'desc')
                ->get(['id', 'date', 'reason'])
                ->map(fn ($appointment) => [
                    'id' => $appointment->id,
                    'date' => $appointment->date->toDateString(),
                    'reason' => $appointment->reason,
                ]),
            'encounter_notes' => Inertia::defer(fn () => $patient->encounterNotes()
                ->with(['author', 'signer', 'coSigner'])
                ->orderBy('encounter_date', 'desc')
                ->get()
                ->map(fn (EncounterNote $note) => [
                    'id' => $note->id,
                    'type' => $note->type->value,
                    'type_label' => $note->type->label(),
                    'encounter_date' => $note->encounter_date->toDateString(),
                    'title' => $note->title,
                    'content' => $note->content,
                    'status' => $note->status->value,
                    'status_label' => $note->status->label(),
                    'appointment_id' => $note->appointment_id,
                    'author_id' => $note->author_id,
                    'author_name' => trim("{$note->author->first_name} {$note->author->last_name}"),
                    'signer_name' => $note->signer ? trim("{$note->signer->first_name} {$note->signer->last_name}") : null,
                    'co_signer_name' => $note->coSigner ? trim("{$note->coSigner->first_name} {$note->coSigner->last_name}") : null,
                    'signed_at' => $note->signed_at?->toDateString(),
                    'co_signed_at' => $note->co_signed_at?->toDateString(),
                    'can_edit' => $user->can('update', $note),
                    'can_delete' => $user->can('delete', $note),
                    'can_sign' => $user->can('sign', $note),
                    'can_co_sign' => $user->can('coSign', $note),
                    'can_unsign' => $user->can('unsign', $note),
                ])),
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

    private function uploaderName(Document $document): ?string
    {
        $uploader = $document->uploader;

        if ($uploader === null) {
            return null;
        }

        return trim("{$uploader->first_name} {$uploader->last_name}");
    }
}
