<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Http\Requests\AppointmentRequest;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use Inertia\Inertia;

class AppointmentController extends Controller
{
    public function index()
    {
        return AppointmentResource::collection(Appointment::all());
    }

    public function create()
    {
        return Inertia::render('Appointments/Create', [
            'appointment' => new AppointmentResource(new Appointment()),
            'statuses'    => AppointmentStatus::toArray()
        ]);
    }

    public function store(AppointmentRequest $request)
    {
        return new AppointmentResource(Appointment::create($request->validated()));
    }

    public function show(Appointment $appointment)
    {
        return new AppointmentResource($appointment);
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
