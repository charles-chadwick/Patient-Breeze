<?php

namespace App\Models;

use App\Contracts\LinksActivityToPatient;
use App\Enums\BodyPosition;
use App\Enums\OxygenDelivery;
use App\Enums\TemperatureSite;
use App\Enums\VitalType;
use Database\Factories\PatientVitalsFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class PatientVitals extends Model implements LinksActivityToPatient
{
    /** @use HasFactory<PatientVitalsFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $table = 'patient_vitals';

    protected $fillable = [
        'patient_id',
        'appointment_id',
        'recorded_by',
        'measured_at',
        'systolic',
        'diastolic',
        'position',
        'heart_rate',
        'respiratory_rate',
        'temperature',
        'temperature_site',
        'oxygen_saturation',
        'oxygen_delivery',
        'weight',
        'height',
        'pain_score',
        'notes',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'measured_at' => 'datetime',
            'systolic' => 'integer',
            'diastolic' => 'integer',
            'position' => BodyPosition::class,
            'heart_rate' => 'integer',
            'respiratory_rate' => 'integer',
            'temperature' => 'decimal:1',
            'temperature_site' => TemperatureSite::class,
            'oxygen_saturation' => 'integer',
            'oxygen_delivery' => OxygenDelivery::class,
            'weight' => 'decimal:2',
            'height' => 'decimal:2',
            'pain_score' => 'integer',
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

    /**
     * The staff member who charted the readings.
     */
    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * Body mass index derived from the recorded weight and height, rounded to one
     * decimal. Null unless both measurements are present, so it never drifts from
     * the values it is computed from.
     *
     * @return Attribute<float|null, never>
     */
    protected function bmi(): Attribute
    {
        return Attribute::get(function (): ?float {
            $weight = $this->weight;
            $height = $this->height;

            if ($weight === null || $height === null || (float) $height <= 0) {
                return null;
            }

            $metres = (float) $height / 100;

            return round((float) $weight / ($metres ** 2), 1);
        });
    }

    /**
     * The vital types whose readings fall outside the adult normal range, as a
     * list of their enum values — used to highlight out-of-range flowsheet cells.
     *
     * @return list<string>
     */
    public function abnormalFlags(): array
    {
        $flags = [];

        foreach (VitalType::cases() as $type) {
            if ($type->isAbnormal($this->{$type->column()})) {
                $flags[] = $type->value;
            }
        }

        return $flags;
    }

    /**
     * A compact "120/80" rendering of the blood-pressure pair, or null when
     * neither figure was recorded.
     */
    public function bloodPressureLabel(): ?string
    {
        if ($this->systolic === null && $this->diastolic === null) {
            return null;
        }

        return sprintf('%s/%s', $this->systolic ?? '—', $this->diastolic ?? '—');
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
