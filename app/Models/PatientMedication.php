<?php

namespace App\Models;

use App\Contracts\LinksActivityToPatient;
use App\Enums\DoseForm;
use App\Enums\Frequency;
use Database\Factories\PatientMedicationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class PatientMedication extends Model implements LinksActivityToPatient
{
    /** @use HasFactory<PatientMedicationFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'patient_id',
        'type',
        'name',
        'dosage',
        'dose_form',
        'frequency',
        'amount',
        'ndc',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'dose_form' => DoseForm::class,
            'frequency' => Frequency::class,
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
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
