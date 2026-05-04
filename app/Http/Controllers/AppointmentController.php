<?php

namespace App\Http\Controllers;

use App\Actions\BookAppointmentAction;
use App\Actions\UpdateAppointmentAction;
use App\Enums\AppointmentRole;
use App\Enums\AppointmentStatus;
use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Requests\UpdateAppointmentRequest;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class AppointmentController extends Controller
{
    public function __construct(
        private BookAppointmentAction $bookAction,
        private UpdateAppointmentAction $updateAction,
    ) {}

    public function index(Request $request): Response
    {
        $date = Carbon::parse($request->string('date', 'today')->toString())->startOfDay();
        $view = $request->input('view') === 'day' ? 'day' : 'week';
        $search = $request->string('search')->trim();
        $staff_ids = array_values(array_filter(array_map('intval', (array) $request->input('staff', []))));

        [$range_start, $range_end] = $view === 'day'
            ? [$date->copy(), $date->copy()]
            : [$date->copy()->startOfWeek(), $date->copy()->endOfWeek()];

        $appointments = Appointment::with(['patient.media', 'users.media'])
            ->forDateRange($range_start, $range_end)
            ->when($search, fn (Builder $query) => $query->whereHas(
                'patient',
                fn (Builder $q) => $q->where(fn (Builder $inner) => $inner
                    ->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                )
            ))
            ->when($staff_ids, fn (Builder $query) => $query->whereHas(
                'users',
                fn (Builder $q) => $q->whereIn('users.id', $staff_ids)
            ))
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        return Inertia::render('Appointments/Index', [
            'appointments' => $appointments,
            'date' => $date->toDateString(),
            'view' => $view,
            'search' => $search->toString(),
            'staff' => $staff_ids,
            'staff_options' => User::staff()->with('media')->orderBy('last_name')->get(['id', 'first_name', 'last_name']),
        ]);
    }

    public function create(Patient $patient): Response
    {
        return Inertia::render('Appointments/Form', [
            'patient' => $patient->load('media'),
            ...$this->sharedProps(),
        ]);
    }

    public function store(StoreAppointmentRequest $request, Patient $patient): RedirectResponse
    {
        $this->bookAction->execute($patient, $request->validated());

        return redirect()->route('patients.show', $patient);
    }

    public function edit(Patient $patient, Appointment $appointment): Response
    {
        $appointment->load('users');

        return Inertia::render('Appointments/Form', [
            'patient' => $patient->load('media'),
            'appointment' => $appointment,
            ...$this->sharedProps(),
        ]);
    }

    public function update(UpdateAppointmentRequest $request, Patient $patient, Appointment $appointment): RedirectResponse
    {
        $this->updateAction->execute($appointment, $request->validated());

        return redirect()->route('patients.show', $patient);
    }

    /**
     * @return array<string, mixed>
     */
    private function sharedProps(): array
    {
        return [
            'status_options' => array_column(AppointmentStatus::cases(), 'value'),
            'role_options' => array_column(AppointmentRole::cases(), 'value'),
            'staff_options' => User::staff()->with('media')->orderBy('last_name')->get(['id', 'first_name', 'last_name']),
        ];
    }
}
