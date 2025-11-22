<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Http\Requests\AppointmentRequest;
use App\Http\Resources\AppointmentResource;
use App\Http\Resources\PatientResource;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Inertia\Inertia;
use function request;

class AppointmentController extends Controller
{
    public function index()
    {
        return AppointmentResource::collection(Appointment::all());
    }

    public function create()
    {
        $patient = Patient::find(request()->patient_id);

        $users = User::get()->mapWithKeys(function ($user) {
            return ['value' => $user->id, 'label' => $user->full_name];
        });

        return Inertia::render('Appointments/Form', [
            'action' => 'create',
            'appointment' => new AppointmentResource(new Appointment()),
            'statuses'    => AppointmentStatus::toArray(),
            'patient'     => new PatientResource($patient),
            'users'       => $users,
        ]);
    }

    public function store(AppointmentRequest $request)
    {
        $appointment = Appointment::create($request->validated());
        return redirect()->route('appointments.show', $appointment);
    }

    public function show(Appointment $appointment)
    {
        $appointment->load([
            'patient',
            'created_by',
        ]);
        return Inertia::render('Appointments/Show', ['appointment' => new AppointmentResource($appointment)]);
    }

    public function edit(Appointment $appointment)
    {
        $patient = Patient::find(request()->patient_id);

        // load relations
        $appointment->load(['users']);

        $users = User::get()->mapWithKeys(function ($user) {
            return ['name' => $user->id, 'label' => $user->full_name];
        });

        return Inertia::render('Appointments/Form', [
            'action' => 'edit',
            'appointment' => new AppointmentResource($appointment),
            'statuses'    => AppointmentStatus::toArray(),
            'patient'     => new PatientResource($patient),
            'users'       => $users,
        ]);
    }

    public function update(AppointmentRequest $request, Appointment $appointment)
    {
        $appointment->update($request->validated());

        return new AppointmentResource($appointment);
    }

    public function destroy(Appointment $appointment)
    {
        $appointment->delete();

        return response()->json();
    }
}
