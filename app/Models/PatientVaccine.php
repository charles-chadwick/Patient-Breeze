<?php

namespace App\Models;

use App\Contracts\LinksActivityToPatient;
use App\Enums\VaccineRoute;
use App\Enums\VaccineSite;
use App\Enums\VaccineStatus;
use Database\Factories\PatientVaccineFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class PatientVaccine extends Model implements LinksActivityToPatient
{
    /** @use HasFactory<PatientVaccineFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $table = 'patient_vaccines';

    protected $fillable = [
        'patient_id',
        'vaccine',
        'cvx_code',
        'administered_on',
        'dose_number',
        'status',
        'route',
        'site',
        'dose_amount',
        'manufacturer',
        'lot_number',
        'expires_on',
        'administered_by',
        'notes',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'administered_on' => 'date',
            'dose_number' => 'integer',
            'status' => VaccineStatus::class,
            'route' => VaccineRoute::class,
            'site' => VaccineSite::class,
            'expires_on' => 'date',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * The staff member who gave the dose.
     */
    public function administeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'administered_by');
    }

    /**
     * Only doses that actually went into the patient, i.e. the immunization
     * history proper rather than refusals and corrections.
     */
    public function scopeAdministered(Builder $query): void
    {
        $query->where('status', VaccineStatus::Completed);
    }

    /**
     * Whether this dose's lot was already past its expiration date when it was
     * given — worth surfacing, since it may mean the dose does not count.
     */
    public function wasExpiredWhenAdministered(): bool
    {
        if ($this->expires_on === null || ! $this->status->isAdministered()) {
            return false;
        }

        return $this->expires_on->lessThan($this->administered_on);
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
