<?php
/** @noinspection PhpUndefinedFieldInspection */

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Http\Requests\AppointmentRequest;
use App\Http\Resources\AppointmentResource;
use App\Http\Resources\PatientResource;
use App\Http\Resources\UserResource;
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

        $users = [];
        foreach (User::get() as $user) {
            $users[] = [
                'value' => $user->id,
                'label' => $user->full_name
            ];
        }

        return Inertia::render('Appointments/Form', [
            'action'      => 'create',
            'appointment' => new AppointmentResource(new Appointment()),
            'statuses'    => AppointmentStatus::toArray(),
            'patient'     => new PatientResource($patient),
            'users'       => $users,
        ]);
    }

    public function store(AppointmentRequest $request)
    {
        $appointment = Appointment::create($request->validated());
        $appointment->users()
            ->sync(request('user_ids'));
        return redirect()->route('appointments.show', $appointment);
    }

    public function show(Appointment $appointment)
    {
        $appointment->load([
            'users',
            'patient',
            'created_by',
        ]);

        $appointment = new AppointmentResource($appointment);

        $users = User::get()
            ->mapWithKeys(function ($user) {
                return [
                    $user->id => [
                        'value' => $user->id,
                        'label' => $user->full_name
                    ]
                ];
            });

        return Inertia::render('Appointments/Show', [
            'patient'     => new PatientResource($appointment->patient),
            'appointment' => $appointment,
            'statuses'    => AppointmentStatus::toArray(),
            'users'       => $users,
        ]);
    }

    public function edit(Appointment $appointment)
    {
        // load relations
        $appointment->load(['users', 'patient']);;

        $users = User::get()
            ->mapWithKeys(function ($user) {
                return [
                    $user->id => [
                        'value' => $user->id,
                        'label' => $user->full_name
                    ]
                ];
            })->toArray();

        return Inertia::render('Appointments/Form', [
            'action'      => 'update',
            'appointment' => new AppointmentResource($appointment),
            'statuses'    => AppointmentStatus::toArray(),
            'patient'     => new PatientResource($appointment->patient),
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
