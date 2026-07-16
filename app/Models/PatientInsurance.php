<?php

namespace App\Models;

use App\Contracts\LinksActivityToPatient;
use App\Enums\InsurancePlanType;
use App\Enums\InsurancePriority;
use App\Enums\SubscriberRelationship;
use Database\Factories\PatientInsuranceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class PatientInsurance extends Model implements LinksActivityToPatient
{
    /** @use HasFactory<PatientInsuranceFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'patient_id',
        'insurance_company_id',
        'member_id',
        'group_number',
        'plan_type',
        'priority',
        'subscriber_name',
        'relationship_to_subscriber',
        'effective_on',
        'terminates_on',
        'notes',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'plan_type' => InsurancePlanType::class,
            'priority' => InsurancePriority::class,
            'relationship_to_subscriber' => SubscriberRelationship::class,
            'effective_on' => 'date',
            'terminates_on' => 'date',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function insuranceCompany(): BelongsTo
    {
        return $this->belongsTo(InsuranceCompany::class);
    }

    /**
     * Whether this policy is in force today — it has taken effect and has not
     * yet reached a termination date.
     */
    public function isActive(): bool
    {
        $today = Carbon::today();

        if ($this->effective_on !== null && $this->effective_on->greaterThan($today)) {
            return false;
        }

        return $this->terminates_on === null || $this->terminates_on->greaterThanOrEqualTo($today);
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
