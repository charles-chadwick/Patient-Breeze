<?php

/** @noinspection PhpIncompatibleReturnTypeInspection */

namespace App\Models;

use App\Contracts\LinksActivityToPatient;
use App\Enums\AppointmentRole;
use App\Enums\AppointmentStatus;
use Database\Factories\AppointmentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Appointment extends Model implements LinksActivityToPatient
{
    /** @use HasFactory<AppointmentFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'patient_id',
        'date',
        'start_time',
        'end_time',
        'status',
        'reason',
        'notes',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    public function primaryProvider(): ?User
    {
        return $this->users()
            ->wherePivot('role', AppointmentRole::Primary->value)
            ->first();
    }

    public function attachProvider(User $user, AppointmentRole $role = AppointmentRole::Primary): void
    {
        $this->users()->syncWithoutDetaching([
            $user->id => ['role' => $role->value],
        ]);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function scopeForDateRange(Builder $query, Carbon $start, Carbon $end): void
    {
        $query->whereBetween('date', [$start->toDateString(), $end->copy()->endOfDay()->toDateTimeString()]);
    }

    public function scopeForDate(Builder $query, mixed $date): void
    {
        $query->whereDate('date', $date);
    }

    public function scopeWithStatus(Builder $query, AppointmentStatus ...$statuses): void
    {
        $query->whereIn('status', array_column($statuses, 'value'));
    }

    public function scopeMatchingReasonOrNotes(Builder $query, string $search): void
    {
        $query->where(fn (Builder $query) => $query
            ->where('reason', 'like', "%{$search}%")
            ->orWhere('notes', 'like', "%{$search}%")
        );
    }

    public function scopeMatchingReasonOrPatientName(Builder $query, string $search): void
    {
        $query->where(fn (Builder $query) => $query
            ->where('reason', 'like', "%{$search}%")
            ->orWhereHas('patient', fn (Builder $query) => $query->matchingName($search))
        );
    }

    public function scopeMatchingPatientName(Builder $query, string $search): void
    {
        $query->whereHas('patient', fn (Builder $query) => $query->matchingName($search));
    }

    /**
     * Build the calendar listing (day/week range, search, staff filter) and its
     * resolved query parameters. Only the staff currently applied to the filter
     * are resolved for display; the picker searches the full list on demand.
     *
     * @return array{appointments: Collection<int, Appointment>, date: string, view: string, search: string, staff: list<int>, selected_staff: array<int, array{id: int, first_name: string, last_name: string, avatar_url: string}>}
     */
    public function scopeCalendar(Builder $query, Request $request): array
    {
        $date = Carbon::parse($request->string('date', 'today')->toString())->startOfDay();
        $view = $request->input('view') === 'day' ? 'day' : 'week';
        $search = $request->string('search')->trim()->toString();
        $staff_ids = array_values(array_filter(array_map('intval', (array) $request->input('staff', []))));

        [$range_start, $range_end] = $view === 'day'
            ? [$date->copy(), $date->copy()]
            : [$date->copy()->startOfWeek(), $date->copy()->endOfWeek()];

        return [
            'appointments' => $query->with(['patient.media', 'users.media'])
                ->forDateRange($range_start, $range_end)
                ->when($search, fn (Builder $query) => $query->matchingPatientName($search))
                ->when($staff_ids, fn (Builder $query) => $query->whereHas(
                    'users',
                    fn (Builder $query) => $query->whereIn('users.id', $staff_ids)
                ))
                ->orderBy('date')
                ->orderBy('start_time')
                ->get(),
            'date' => $date->toDateString(),
            'view' => $view,
            'search' => $search,
            'staff' => $staff_ids,
            'selected_staff' => $staff_ids === []
                ? []
                : User::whereIn('id', $staff_ids)
                    ->with(['media' => fn ($query) => $query->where('collection_name', 'avatar')])
                    ->orderBy('last_name')
                    ->get(['id', 'first_name', 'last_name'])
                    ->map(fn (User $user): array => [
                        'id' => $user->id,
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                        'avatar_url' => $user->avatar_url,
                    ])
                    ->all(),
        ];
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

    public function auditPatientId(): ?int
    {
        return $this->patient_id;
    }
}
