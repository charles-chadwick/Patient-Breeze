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
use Spatie\LaravelPdf\Enums\Format;
use Spatie\LaravelPdf\PdfBuilder;

use function Spatie\LaravelPdf\Support\pdf;

class AuditLogController extends Controller
{
    /**
     * Roles allowed to view and export the audit log.
     *
     * @var list<string>
     */
    private const ALLOWED_ROLES = [
        UserRole::SuperAdmin->value,
        UserRole::Doctor->value,
        UserRole::Staff->value,
    ];

    /**
     * The most entries a single PDF export will render, guarding against a
     * runaway document when broad filters match the whole activity table.
     */
    private const EXPORT_LIMIT = 2000;

    public function index(Request $request): Response
    {
        $this->authorizeAccess($request);

        $filters = $this->filters($request);
        $patient = $this->resolvePatient($filters['patient_id']);

        $activities = $this->query($filters)
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Activity $activity) => ActivityPresenter::present($activity));

        return Inertia::render('AuditLog/Index', [
            'activities' => $activities,
            'filters' => $filters,
            'patient' => $this->presentPatient($patient),
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

    public function export(Request $request): PdfBuilder
    {
        $this->authorizeAccess($request);

        $filters = $this->filters($request);
        $patient = $this->resolvePatient($filters['patient_id']);

        $activities = $this->query($filters)
            ->limit(self::EXPORT_LIMIT + 1)
            ->get()
            ->map(fn (Activity $activity) => ActivityPresenter::present($activity));

        $truncated = $activities->count() > self::EXPORT_LIMIT;

        $filename = $patient
            ? "audit-log-patient-{$patient->id}.pdf"
            : 'audit-log.pdf';

        return pdf('pdf.audit-log', [
            'activities' => $activities->take(self::EXPORT_LIMIT),
            'patient' => $this->presentPatient($patient),
            'filters' => $filters,
            'filterSummary' => $this->filterSummary($filters),
            'truncated' => $truncated,
            'limit' => self::EXPORT_LIMIT,
            'generatedAt' => now(),
        ])
            ->format(Format::A4)
            ->landscape()
            ->name($filename)
            ->download();
    }

    private function authorizeAccess(Request $request): void
    {
        abort_unless(
            $request->user()?->hasRole(self::ALLOWED_ROLES) ?? false,
            403
        );
    }

    /**
     * @return array{causer_id: int|null, subject_type: string|null, event: string|null, date_from: string|null, date_to: string|null, patient_id: int|null}
     */
    private function filters(Request $request): array
    {
        return [
            'causer_id' => $request->integer('causer_id') ?: null,
            'subject_type' => $request->string('subject_type')->toString() ?: null,
            'event' => $request->string('event')->toString() ?: null,
            'date_from' => $request->date('date_from')?->toDateString(),
            'date_to' => $request->date('date_to')?->toDateString(),
            'patient_id' => $request->integer('patient_id') ?: null,
        ];
    }

    /**
     * @param  array{causer_id: int|null, subject_type: string|null, event: string|null, date_from: string|null, date_to: string|null, patient_id: int|null}  $filters
     * @return Builder<Activity>
     */
    private function query(array $filters): Builder
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

    private function resolvePatient(?int $patient_id): ?Patient
    {
        return $patient_id ? Patient::find($patient_id) : null;
    }

    /**
     * @return array{id: int, name: string}|null
     */
    private function presentPatient(?Patient $patient): ?array
    {
        return $patient ? [
            'id' => $patient->id,
            'name' => trim("{$patient->first_name} {$patient->last_name}"),
        ] : null;
    }

    /**
     * A human-readable list of the active filters for the PDF header.
     *
     * @param  array{causer_id: int|null, subject_type: string|null, event: string|null, date_from: string|null, date_to: string|null, patient_id: int|null}  $filters
     * @return list<string>
     */
    private function filterSummary(array $filters): array
    {
        $summary = [];

        if ($filters['causer_id']) {
            $causer = User::find($filters['causer_id']);
            $summary[] = __('audit.export.filter_user', [
                'name' => $causer ? trim("{$causer->first_name} {$causer->last_name}") : (string) $filters['causer_id'],
            ]);
        }

        if ($filters['subject_type']) {
            $summary[] = __('audit.export.filter_record', [
                'type' => __('audit.subjects.'.class_basename($filters['subject_type'])),
            ]);
        }

        if ($filters['event']) {
            $summary[] = __('audit.export.filter_action', ['action' => __('audit.actions.'.$filters['event'])]);
        }

        if ($filters['date_from']) {
            $summary[] = __('audit.export.filter_from', ['date' => $filters['date_from']]);
        }

        if ($filters['date_to']) {
            $summary[] = __('audit.export.filter_to', ['date' => $filters['date_to']]);
        }

        return $summary;
    }
}
