<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Http\Requests\AppointmentRequest;
use App\Http\Resources\AppointmentResource;
use App\Http\Resources\PatientResource;
use App\Models\Appointment;
use App\Models\Patient;
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

        return Inertia::render('Appointments/Create', [
            'appointment' => new AppointmentResource(new Appointment()),
            'statuses'    => AppointmentStatus::toArray(),
            'patient'     => new PatientResource($patient),
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
        return Inertia::render('Appointments/Edit', ['appointment' => new AppointmentResource($appointment)]);
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
