<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Patient;
use App\Models\User;
use App\Support\ActivityPresenter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless(
            $request->user()?->hasRole([
                UserRole::SuperAdmin->value,
                UserRole::Doctor->value,
                UserRole::Staff->value,
            ]) ?? false,
            403
        );

        $causer_id = $request->integer('causer_id') ?: null;
        $subject_type = $request->string('subject_type')->toString() ?: null;
        $event = $request->string('event')->toString() ?: null;
        $date_from = $request->date('date_from');
        $date_to = $request->date('date_to');
        $patient_id = $request->integer('patient_id') ?: null;

        // When scoped to a patient, surface their name so the page can show it
        // in a banner rather than only echoing the raw id.
        $patient = $patient_id ? Patient::find($patient_id) : null;

        $activities = Activity::query()
            ->with('causer')
            ->when($patient_id, fn (Builder $query, int $id) => $query->where('patient_id', $id))
            ->when($causer_id, fn (Builder $query, int $id) => $query
                ->where('causer_type', User::class)
                ->where('causer_id', $id))
            ->when($subject_type, fn (Builder $query, string $type) => $query->where('subject_type', $type))
            ->when($event, fn (Builder $query, string $value) => $query->where('event', $value))
            ->when($date_from, fn (Builder $query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($date_to, fn (Builder $query, $date) => $query->whereDate('created_at', '<=', $date))
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Activity $activity) => ActivityPresenter::present($activity));

        return Inertia::render('AuditLog/Index', [
            'activities' => $activities,
            'filters' => [
                'causer_id' => $causer_id,
                'subject_type' => $subject_type,
                'event' => $event,
                'date_from' => $date_from?->toDateString(),
                'date_to' => $date_to?->toDateString(),
                'patient_id' => $patient_id,
            ],
            'patient' => $patient ? [
                'id' => $patient->id,
                'name' => trim("{$patient->first_name} {$patient->last_name}"),
            ] : null,
            'causer_options' => User::orderBy('first_name')->orderBy('last_name')
                ->get(['id', 'first_name', 'last_name'])
                ->map(fn (User $user): array => [
                    'id' => $user->id,
                    'name' => trim("{$user->first_name} {$user->last_name}"),
                ]),
            'subject_options' => Activity::query()
                ->whereNotNull('subject_type')
                ->distinct()
                ->orderBy('subject_type')
                ->pluck('subject_type')
                ->map(fn (string $type): array => [
                    'value' => $type,
                    'key' => class_basename($type),
                ]),
            'event_options' => ['created', 'updated', 'deleted'],
        ]);
    }
}
