<?php

namespace App\Http\Controllers;

use App\Actions\CreatePatientAction;
use App\Actions\UpdatePatientAction;
use App\Enums\BloodType;
use App\Enums\ContactType;
use App\Enums\DiscussionType;
use App\Enums\DocumentType;
use App\Enums\DoseForm;
use App\Enums\Frequency;
use App\Enums\GenderAtBirth;
use App\Enums\GenderIdentity;
use App\Enums\NoteType;
use App\Http\Requests\StorePatientRequest;
use App\Http\Requests\UpdatePatientRequest;
use App\Models\Document;
use App\Models\Patient;
use App\Models\PatientMedication;
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

    private function uploaderName(Document $document): ?string
    {
        $uploader = $document->uploader;

        if ($uploader === null) {
            return null;
        }

        return trim("{$uploader->first_name} {$uploader->last_name}");
    }
}
