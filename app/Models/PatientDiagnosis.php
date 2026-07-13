<?php

namespace App\Models;

use App\Contracts\LinksActivityToPatient;
use App\Enums\DiagnosisStatus;
use Database\Factories\PatientDiagnosisFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class PatientDiagnosis extends Model implements LinksActivityToPatient
{
    /** @use HasFactory<PatientDiagnosisFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'patient_id',
        'diagnosis',
        'icd10_code',
        'diagnosed_on',
        'status',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'diagnosed_on' => 'date',
            'status' => DiagnosisStatus::class,
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
