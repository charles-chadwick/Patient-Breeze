<?php

namespace App\Models;

use App\Casts\FlexibleValue;
use App\Contracts\LinksActivityToPatient;
use App\Enums\GenderAtBirth;
use App\Enums\ResultFlag;
use Database\Factories\PatientLabResultFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class PatientLabResult extends Model implements LinksActivityToPatient
{
    /** @use HasFactory<PatientLabResultFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'patient_id',
        'lab_order_id',
        'name',
        'performing_lab',
        'cpt_code',
        'value',
        'unit',
        'reference_low',
        'reference_high',
        'reference_gender',
        'reference_age',
        'collected_at',
        'notes',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'value' => FlexibleValue::class,
            'reference_low' => FlexibleValue::class,
            'reference_high' => FlexibleValue::class,
            'reference_gender' => GenderAtBirth::class,
            'reference_age' => 'integer',
            'collected_at' => 'date',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function labOrder(): BelongsTo
    {
        return $this->belongsTo(LabOrder::class);
    }

    /**
     * Where the measured value falls relative to the snapshotted reference range.
     *
     * @return Attribute<ResultFlag, never>
     */
    protected function flag(): Attribute
    {
        return Attribute::get(function (): ResultFlag {
            $value = $this->value;
            $low = $this->reference_low;
            $high = $this->reference_high;

            $isNumeric = fn (mixed $candidate): bool => is_int($candidate) || is_float($candidate);

            // Low/High can only be judged when the value and at least one bound are numeric.
            if (! $isNumeric($value) || (! $isNumeric($low) && ! $isNumeric($high))) {
                return ResultFlag::Unknown;
            }

            if ($isNumeric($low) && $value < $low) {
                return ResultFlag::Low;
            }

            if ($isNumeric($high) && $value > $high) {
                return ResultFlag::High;
            }

            return ResultFlag::Normal;
        });
    }

    /**
     * A human-friendly rendering of the snapshotted reference range, e.g.
     * "13.5–17.5 g/dL", "< 200 mg/dL".
     */
    public function referenceLabel(): string
    {
        $unit = $this->unit ? ' '.$this->unit : '';
        $low = $this->getRawOriginal('reference_low');
        $high = $this->getRawOriginal('reference_high');

        if ($low !== null && $high !== null) {
            return "{$low}–{$high}{$unit}";
        }

        if ($high !== null) {
            return "< {$high}{$unit}";
        }

        if ($low !== null) {
            return "> {$low}{$unit}";
        }

        return __('lab_results.no_range');
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
