<?php

namespace App\Models;

use App\Contracts\LinksActivityToPatient;
use App\Enums\AllergenCategory;
use App\Enums\AllergyReaction;
use App\Enums\AllergySeverity;
use App\Enums\AllergyStatus;
use Database\Factories\PatientAllergyFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class PatientAllergy extends Model implements LinksActivityToPatient
{
    /** @use HasFactory<PatientAllergyFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $table = 'patient_allergies';

    protected $fillable = [
        'patient_id',
        'allergen',
        'category',
        'reactions',
        'severity',
        'status',
        'onset_on',
        'notes',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'category' => AllergenCategory::class,
            'reactions' => 'array',
            'severity' => AllergySeverity::class,
            'status' => AllergyStatus::class,
            'onset_on' => 'date',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Only allergies that still represent a live risk, ordered most dangerous
     * first so the chart banner leads with the worst one.
     */
    public function scopeCurrent(Builder $query): void
    {
        $query->where('status', AllergyStatus::Active);
    }

    /**
     * The recorded reactions as enum cases, skipping any value that is no longer
     * a known reaction.
     *
     * @return list<AllergyReaction>
     */
    public function reactionCases(): array
    {
        return array_values(array_filter(array_map(
            fn (string $reaction): ?AllergyReaction => AllergyReaction::tryFrom($reaction),
            $this->reactions ?? [],
        )));
    }

    /**
     * The recorded reactions as translated labels, for display.
     *
     * @return list<string>
     */
    public function reactionLabels(): array
    {
        return array_map(fn (AllergyReaction $reaction): string => $reaction->label(), $this->reactionCases());
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
