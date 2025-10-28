<?php

namespace App\Http\Controllers;

use App\Enums\Gender;
use App\Http\Requests\PatientRequest;
use App\Http\Resources\AppointmentResource;
use App\Http\Resources\PatientResource;
use App\Models\Appointment;
use App\Models\Patient;
use Hash;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class PatientController extends Controller
{
    public function index()
    {
        $patients = Patient::with('created_by')
            ->orderBy(request('sort_by', 'id'), request('sort_direction', 'asc'))
            ->paginate()
            ->withQueryString();

        return Inertia::render('Patients/Index', [
            'patients' => PatientResource::collection($patients),
        ]);
    }

    public function create()
    {
        $genders = collect(Gender::cases())
            ->map(function ($role) {
                return [
                    'value' => $role->value,
                    'name'  => $role->name,
                ];
            })
            ->toArray();

        return Inertia::render('Patients/Create', ['genders' => $genders]);
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
