<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDiagnosisRequest;
use App\Http\Requests\UpdateDiagnosisRequest;
use App\Models\Diagnosis;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DiagnosisController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Diagnosis::class);

        return Inertia::render('Diagnoses/Index', [
            ...Diagnosis::listing($request),
        ]);
    }

    /**
     * Search the diagnosis catalog for the "add diagnosis" modal.
     */
    public function search(Request $request): JsonResponse
    {
        $search = $request->string('search')->trim()->toString();

        return response()->json(['diagnoses' => Diagnosis::searchCatalog($search)]);
    }

    public function create(): Response
    {
        $this->authorize('create', Diagnosis::class);

        return Inertia::render('Diagnoses/Form');
    }

    public function store(StoreDiagnosisRequest $request): RedirectResponse
    {
        $this->authorize('create', Diagnosis::class);

        Diagnosis::create($request->validated());

        return redirect()->route('diagnoses.index')
            ->with('success', __('flash.diagnoses.created'));
    }

    public function edit(Diagnosis $diagnosis): Response
    {
        $this->authorize('update', $diagnosis);

        return Inertia::render('Diagnoses/Form', [
            'diagnosis' => $diagnosis,
        ]);
    }

    public function update(UpdateDiagnosisRequest $request, Diagnosis $diagnosis): RedirectResponse
    {
        $this->authorize('update', $diagnosis);

        $diagnosis->update($request->validated());

        return redirect()->route('diagnoses.index')
            ->with('success', __('flash.diagnoses.updated'));
    }

    public function destroy(Diagnosis $diagnosis): RedirectResponse
    {
        $this->authorize('delete', $diagnosis);

        $diagnosis->delete();

        return redirect()->route('diagnoses.index')
            ->with('success', __('flash.diagnoses.deleted'));
    }
}
