<?php

namespace App\Models;

use App\Enums\DoseForm;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PatientMedication extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'patient_id',
        'type',
        'name',
        'dosage',
        'dose_form',
        'ndc',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'dose_form' => DoseForm::class,
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
