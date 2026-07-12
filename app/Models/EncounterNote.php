<?php

namespace App\Models;

use App\Contracts\LinksActivityToPatient;
use App\Enums\EncounterNoteStatus;
use App\Enums\EncounterNoteType;
use App\Models\Concerns\Searchable;
use App\Models\Concerns\Sortable;
use Database\Factories\EncounterNoteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class EncounterNote extends Model implements LinksActivityToPatient
{
    /** @use HasFactory<EncounterNoteFactory> */
    use HasFactory, LogsActivity, Searchable, SoftDeletes, Sortable;

    protected $fillable = [
        'type',
        'encounter_date',
        'title',
        'content',
        'appointment_id',
    ];

    protected function searchableFields(): array
    {
        return ['title', 'content'];
    }

    protected function sortableFields(): array
    {
        return [
            'title' => 'title',
            'type' => 'type',
            'encounter_date' => 'encounter_date',
            'status' => 'status',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function signer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signed_by');
    }

    public function coSigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'co_signed_by');
    }

    public function isEditable(): bool
    {
        return $this->status === EncounterNoteStatus::Unsigned;
    }

    protected function casts(): array
    {
        return [
            'type' => EncounterNoteType::class,
            'status' => EncounterNoteStatus::class,
            'encounter_date' => 'date',
            'signed_at' => 'datetime',
            'co_signed_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnlyDirty()->logFillable();
    }

    public function auditPatientId(): ?int
    {
        return $this->patient_id;
    }
}
