<?php
/** @noinspection PhpUnused */

namespace App\Models;

use App\Enums\AppointmentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Base
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'type',
        'start',
        'end',
        'status',
        'title',
        'description',
    ];

    protected function casts() : array
    {
        return [
            'start'  => 'datetime',
            'end'    => 'datetime',
            'status' => AppointmentStatus::class,
        ];
    }

    public function patient() : BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
