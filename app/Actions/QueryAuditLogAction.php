<?php

namespace App\Actions;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;

class QueryAuditLogAction
{
    /**
     * Build the filtered, causer-eager-loaded activity query shared by the audit
     * log's paginated index and its PDF export.
     *
     * @param  array{causer_id: int|null, subject_type: string|null, event: string|null, date_from: string|null, date_to: string|null, patient_id: int|null}  $filters
     * @return Builder<Activity>
     */
    public function query(array $filters): Builder
    {
        return Activity::query()
            ->with('causer')
            ->when($filters['patient_id'], fn (Builder $query, int $id) => $query->where('patient_id', $id))
            ->when($filters['causer_id'], fn (Builder $query, int $id) => $query
                ->where('causer_type', User::class)
                ->where('causer_id', $id))
            ->when($filters['subject_type'], fn (Builder $query, string $type) => $query->where('subject_type', $type))
            ->when($filters['event'], fn (Builder $query, string $value) => $query->where('event', $value))
            ->when($filters['date_from'], fn (Builder $query, string $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['date_to'], fn (Builder $query, string $date) => $query->whereDate('created_at', '<=', $date))
            ->latest();
    }

    /**
     * The causer (staff) options offered by the audit log's filter bar.
     *
     * @return Collection<int, array{id: int, name: string}>
     */
    public function causerOptions(): Collection
    {
        return User::nameOptions();
    }

    /**
     * The distinct subject types present in the activity log, for its filter bar.
     *
     * @return Collection<int, array{value: string, key: string}>
     */
    public function subjectOptions(): Collection
    {
        return Activity::query()
            ->whereNotNull('subject_type')
            ->distinct()
            ->orderBy('subject_type')
            ->pluck('subject_type')
            ->map(fn (string $type): array => [
                'value' => $type,
                'key' => class_basename($type),
            ]);
    }

    public function resolvePatient(?int $patient_id): ?Patient
    {
        return $patient_id ? Patient::find($patient_id) : null;
    }
}
