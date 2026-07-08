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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $this->authorize('viewAny', Appointment::class);

        return Inertia::render('Appointments/Index', Appointment::calendar($request));
    }

    /**
     * Search assignable staff for the appointment form's provider picker.
     */
    public function staffSearch(Request $request): JsonResponse
    {
        $search = $request->string('search')->trim()->toString();

        return response()->json(['staff' => User::staff()->forPicker($search)]);
    }

    public function create(Patient $patient): Response
    {
        $this->authorize('create', Appointment::class);

        return Inertia::render('Appointments/Form', [
            'patient' => $patient->load('media'),
            ...$this->sharedProps(),
        ]);
    }

    public function store(StoreAppointmentRequest $request, Patient $patient): RedirectResponse
    {
        $this->authorize('create', Appointment::class);

        $this->bookAction->execute($patient, $request->validated());

        return redirect()->route('patients.show', $patient)
            ->with('success', __('flash.appointments.created'));
    }

    public function edit(Patient $patient, Appointment $appointment): Response
    {
        $this->authorize('update', $appointment);

        $appointment->load('users');

        return Inertia::render('Appointments/Form', [
            'patient' => $patient->load('media'),
            'appointment' => $appointment,
            ...$this->sharedProps(),
        ]);
    }

    public function update(UpdateAppointmentRequest $request, Patient $patient, Appointment $appointment): RedirectResponse
    {
        $this->authorize('update', $appointment);

        $this->updateAction->execute($appointment, $request->validated());

        return redirect()->route('patients.show', $patient)
            ->with('success', __('flash.appointments.updated'));
    }

    /**
     * @return array<string, mixed>
     */
    private function sharedProps(): array
    {
        return [
            'status_options' => array_column(AppointmentStatus::cases(), 'value'),
            'role_options' => array_column(AppointmentRole::cases(), 'value'),
        ];
    }
}
