<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PatientController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim();
        $sort_by = $request->string('sort_by', 'last_name')->toString();
        $direction = $request->input('direction') === 'desc' ? 'desc' : 'asc';

        $patients = Patient::with('user')
            ->when($search, fn ($query) => $query->search($search))
            ->sort($sort_by, $direction)
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Patients/Index', [
            'patients' => $patients,
            'search' => $search->toString(),
            'sort_by' => $sort_by,
            'direction' => $direction,
        ]);
    }

    public function show(Patient $patient): Response
    {
        $patient->load([
            'user',
            'appointments' => fn ($query) => $query->orderBy('date', 'desc')->limit(50),
        ]);

        return Inertia::render('Patients/Show', [
            'patient' => $patient,
        ]);
    }
}
