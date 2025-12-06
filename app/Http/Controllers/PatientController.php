<?php

namespace App\Http\Controllers;

use App\Enums\Gender;
use App\Enums\PatientStatus;
use App\Http\Requests\PatientRequest;
use App\Http\Resources\AppointmentResource;
use App\Http\Resources\PatientResource;
use App\Models\Appointment;
use App\Models\Patient;
use Hash;
use Inertia\Inertia;
use PHPUnit\Logging\OpenTestReporting\Status;
use function request;

class PatientController extends Controller
{
    public function index()
    {
        $patients = Patient::with('created_by')
            ->orderBy(request('sort_by', 'id'), request('sort_direction', 'asc'))
            ->searchAny(request('search'))
            ->paginate()
            ->withQueryString();

        return Inertia::render('Patients/Index', [
            'patients' => PatientResource::collection($patients),
        ]);
    }

    public function create()
    {
        return Inertia::render('Patients/Form', [
            'action'   => 'store',
            'patient'  => new PatientResource(new Patient()),
            'genders'  => Gender::toArray(),
            'statuses' => PatientStatus::toArray(),
        ]);
    }

    public function store(PatientRequest $request)
    {
        $data = $request->validated();
        $data['password'] = Hash::make($request->password);
        $patient = Patient::create($data);

        return to_route('patients.index')->with('message', "{$patient->first_name} {$patient->last_name} created successfully");
    }

    public function chart(Patient $patient)
    {
        $patient->load([
            'created_by',
            'appointments',
        ]);

        $appointments = Appointment::where('patient_id', $patient->id)
            ->with('users')
            ->orderBy('start', 'desc')
            ->get();

        return Inertia::render('Patients/Chart', [
            'patient'      => new PatientResource($patient),
            'appointments' => AppointmentResource::collection($appointments)
        ]);
    }

    public function edit(Patient $patient) {}

    public function update(PatientRequest $request, Patient $patient) {}

    public function destroy(Patient $patient) {}

}
