<?php

namespace App\Http\Controllers;

use App\Http\Requests\PatientRequest;
use App\Http\Resources\PatientResource;
use App\Models\Patient;

class PatientController extends Controller
{
    public function index()
    {
        return PatientResource::collection(Patient::all());
    }

    public function store(PatientRequest $request)
    {
        return new PatientResource(Patient::create($request->validated()));
    }

    public function show(Patient $patient)
    {
        return new PatientResource($patient);
    }

    public function update(PatientRequest $request, Patient $patient)
    {
        $patient->update($request->validated());

        return new PatientResource($patient);
    }

    public function destroy(Patient $patient)
    {
        $patient->delete();

        return response()->json();
    }
}
