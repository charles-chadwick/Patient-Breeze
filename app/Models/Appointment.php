<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use Database\Factories\AppointmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Appointment extends Model
{
    /** @use HasFactory<AppointmentFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'user_id',
        'patient_id',
        'date',
        'start_time',
        'end_time',
        'status',
        'reason',
        'notes',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'status' => AppointmentStatus::class,
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnlyDirty()->logFillable();
    }
}
