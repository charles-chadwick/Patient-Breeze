<?php

namespace App\Models;

use App\Enums\AppointmentRequestStatus;
use Database\Factories\AppointmentRequestFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class AppointmentRequest extends Model
{
    /** @use HasFactory<AppointmentRequestFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'patient_id',
        'user_id',
        'date',
        'start_time',
        'end_time',
        'reason',
        'notes',
        'status',
        'reviewed_by',
        'reviewed_at',
        'appointment_id',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * The staff member the patient requested an appointment with.
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function scopePending(Builder $query): void
    {
        $query->where('status', AppointmentRequestStatus::Pending->value);
    }

    /**
     * Build the pending appointment requests for the staff Portal Queue.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function scopePendingQueue(Builder $query): Collection
    {
        return $query->pending()
            ->with(['patient:id,first_name,last_name,mrn', 'provider:id,first_name,last_name'])
            ->oldest()
            ->get()
            ->map(fn (AppointmentRequest $request): array => [
                'id' => $request->id,
                'date' => $request->date->toDateString(),
                'start_time' => substr($request->start_time, 0, 5),
                'end_time' => substr($request->end_time, 0, 5),
                'reason' => $request->reason,
                'notes' => $request->notes,
                'created_at' => $request->created_at,
                'patient' => $request->patient
                    ? $request->patient->only(['id', 'first_name', 'last_name', 'mrn'])
                    : null,
                'provider' => $request->provider
                    ? $request->provider->only(['id', 'first_name', 'last_name'])
                    : null,
            ]);
    }

    public function isPending(): bool
    {
        return $this->status === AppointmentRequestStatus::Pending;
    }

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'reviewed_at' => 'datetime',
            'status' => AppointmentRequestStatus::class,
        ];
    }
}
