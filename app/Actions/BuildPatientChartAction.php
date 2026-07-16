<?php

namespace App\Actions;

use App\Enums\AllergenCategory;
use App\Enums\AllergyReaction;
use App\Enums\AllergySeverity;
use App\Enums\AllergyStatus;
use App\Enums\AppointmentRole;
use App\Enums\AppointmentStatus;
use App\Enums\ContactType;
use App\Enums\DiagnosisStatus;
use App\Enums\DiscussionType;
use App\Enums\DocumentType;
use App\Enums\DoseForm;
use App\Enums\EncounterNoteType;
use App\Enums\Frequency;
use App\Enums\NoteType;
use App\Enums\VaccineRoute;
use App\Enums\VaccineSite;
use App\Enums\VaccineStatus;
use App\Models\Document;
use App\Models\EncounterNote;
use App\Models\Patient;
use App\Models\PatientAllergy;
use App\Models\PatientDiagnosis;
use App\Models\PatientLabResult;
use App\Models\PatientMedication;
use App\Models\PatientVaccine;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;

class BuildPatientChartAction
{
    /**
     * Assemble every prop the patient chart (Patients/Show) renders: the eager-loaded
     * record, presented chart blocks, filter/enum option lists, and the deferred
     * discussion, notes, and encounter-note threads.
     *
     * @return array<string, mixed>
     */
    public function execute(Patient $patient, Request $request): array
    {
        $user = $request->user();

        $search = $request->string('search')->trim()->toString();

        $patient->load([
            'media',
            'contacts' => fn ($query) => $query->orderBy('name'),
            'documents' => fn ($query) => $query->with(['media', 'uploader'])->latest(),
            'patientMedications' => fn ($query) => $query->latest(),
            'patientDiagnoses' => fn ($query) => $query->latest(),
            'patientLabResults' => fn ($query) => $query->latest(),
            'patientAllergies' => fn ($query) => $query->latest(),
            'allergiesReviewedBy:id,first_name,last_name',
            'patientVaccines' => fn ($query) => $query->orderBy('administered_on', 'desc'),
            'patientVaccines.administeredBy.media',
            'patientVaccines.administeredBy.roles',
        ]);

        return [
            'patient' => $patient,
            'appointments' => $patient->paginatedAppointments($search),
            'appointment_search' => $search,
            'status_options' => array_column(AppointmentStatus::cases(), 'value'),
            'role_options' => array_column(AppointmentRole::cases(), 'value'),
            'documents' => $this->documents($patient),
            'document_type_options' => DocumentType::values(),
            'medications' => $this->medications($patient),
            'dose_form_options' => DoseForm::values(),
            'frequency_options' => Frequency::values(),
            'patient_diagnoses' => $this->diagnoses($patient),
            'diagnosis_status_options' => DiagnosisStatus::values(),
            'patient_allergies' => $this->allergies($patient),
            'allergy_banner' => $patient->allergyBanner(),
            'allergen_category_options' => AllergenCategory::values(),
            'allergy_reaction_options' => AllergyReaction::values(),
            'allergy_severity_options' => AllergySeverity::values(),
            'allergy_status_options' => AllergyStatus::values(),
            'patient_vaccines' => $this->vaccines($patient),
            'vaccine_status_options' => VaccineStatus::values(),
            'vaccine_route_options' => VaccineRoute::values(),
            'vaccine_site_options' => VaccineSite::values(),
            'lab_results' => $this->labResults($patient),
            'contact_types' => ContactType::values(),
            'contactable_type' => Patient::class,
            'discussion_types' => DiscussionType::values(),
            'discussions' => Inertia::defer(fn () => $patient->discussionThread()),
            'note_types' => NoteType::values(),
            'notes' => Inertia::defer(fn () => $patient->notes()->latest()->get()),
            'encounter_note_types' => EncounterNoteType::values(),
            'owner_options' => $this->ownerOptions(),
            'patient_appointments' => $this->appointmentOptions($patient),
            'encounter_notes' => Inertia::defer(fn () => $this->encounterNotes($patient, $user)),
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function documents(Patient $patient): Collection
    {
        return $patient->documents->map(fn (Document $document) => [
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
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function medications(Patient $patient): Collection
    {
        return $patient->patientMedications->map(fn (PatientMedication $medication) => [
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
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function diagnoses(Patient $patient): Collection
    {
        return $patient->patientDiagnoses->map(fn (PatientDiagnosis $diagnosis) => [
            'id' => $diagnosis->id,
            'diagnosis' => $diagnosis->diagnosis,
            'icd10_code' => $diagnosis->icd10_code,
            'diagnosed_on' => $diagnosis->diagnosed_on?->toDateString(),
            'status' => $diagnosis->status->value,
            'status_label' => $diagnosis->status->label(),
            'created_at' => $diagnosis->created_at->toDateString(),
        ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function allergies(Patient $patient): Collection
    {
        return $patient->patientAllergies->map(fn (PatientAllergy $allergy) => [
            'id' => $allergy->id,
            'allergen' => $allergy->allergen,
            'category' => $allergy->category->value,
            'category_label' => $allergy->category->label(),
            'reactions' => $allergy->reactions,
            'reaction_labels' => $allergy->reactionLabels(),
            'severity' => $allergy->severity->value,
            'severity_label' => $allergy->severity->label(),
            'is_critical' => $allergy->severity->isCritical(),
            'status' => $allergy->status->value,
            'status_label' => $allergy->status->label(),
            'onset_on' => $allergy->onset_on?->toDateString(),
            'notes' => $allergy->notes,
            'created_at' => $allergy->created_at->toDateString(),
        ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function vaccines(Patient $patient): Collection
    {
        return $patient->patientVaccines->map(fn (PatientVaccine $vaccine) => [
            'id' => $vaccine->id,
            'vaccine' => $vaccine->vaccine,
            'cvx_code' => $vaccine->cvx_code,
            'administered_on' => $vaccine->administered_on->toDateString(),
            'dose_number' => $vaccine->dose_number,
            'status' => $vaccine->status->value,
            'status_label' => $vaccine->status->label(),
            'is_administered' => $vaccine->status->isAdministered(),
            'route' => $vaccine->route?->value,
            'route_label' => $vaccine->route?->label(),
            'site' => $vaccine->site?->value,
            'site_label' => $vaccine->site?->label(),
            'dose_amount' => $vaccine->dose_amount,
            'manufacturer' => $vaccine->manufacturer,
            'lot_number' => $vaccine->lot_number,
            'expires_on' => $vaccine->expires_on?->toDateString(),
            'was_expired_when_administered' => $vaccine->wasExpiredWhenAdministered(),
            'administered_by' => $vaccine->administeredBy,
            'notes' => $vaccine->notes,
            'created_at' => $vaccine->created_at->toDateString(),
        ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function labResults(Patient $patient): Collection
    {
        return $patient->patientLabResults->map(fn (PatientLabResult $result) => [
            'id' => $result->id,
            'name' => $result->name,
            'performing_lab' => $result->performing_lab,
            'cpt_code' => $result->cpt_code,
            'value' => $result->value,
            'unit' => $result->unit,
            'reference_low' => $result->reference_low,
            'reference_high' => $result->reference_high,
            'reference_label' => $result->referenceLabel(),
            'flag' => $result->flag->value,
            'flag_label' => $result->flag->label(),
            'collected_at' => $result->collected_at?->toDateString(),
            'notes' => $result->notes,
            'created_at' => $result->created_at->toDateString(),
        ]);
    }

    /**
     * The staff owner options offered by the chart's "assign owner" controls.
     *
     * @return Collection<int, array{id: int, name: string}>
     */
    private function ownerOptions(): Collection
    {
        return User::nameOptions();
    }

    /**
     * This patient's appointments as options for linking chart records to a visit.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function appointmentOptions(Patient $patient): Collection
    {
        return $patient->appointments()
            ->orderBy('date', 'desc')
            ->get(['id', 'date', 'reason'])
            ->map(fn ($appointment) => [
                'id' => $appointment->id,
                'date' => $appointment->date->toDateString(),
                'reason' => $appointment->reason,
            ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function encounterNotes(Patient $patient, ?User $user): Collection
    {
        return $patient->encounterNotes()
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
            ]);
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
