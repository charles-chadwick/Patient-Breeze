<?php

namespace App\Http\Controllers;

use App\Enums\DoseForm;
use App\Http\Requests\StoreMedicationRequest;
use App\Http\Requests\UpdateMedicationRequest;
use App\Models\Medication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MedicationController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Medication::class);

        return Inertia::render('Medications/Index', [
            ...Medication::listing($request),
            'dose_form_options' => DoseForm::values(),
        ]);
    }

    /**
     * Search the medication catalog for the "add medication" modal.
     */
    public function search(Request $request): JsonResponse
    {
        $search = $request->string('search')->trim()->toString();

        return response()->json(['medications' => Medication::searchCatalog($search)]);
    }

    public function create(): Response
    {
        $this->authorize('create', Medication::class);

        return Inertia::render('Medications/Form', [
            'dose_form_options' => DoseForm::values(),
        ]);
    }

    public function store(StoreMedicationRequest $request): RedirectResponse
    {
        $this->authorize('create', Medication::class);

        Medication::create($request->validated());

        return redirect()->route('medications.index')
            ->with('success', __('flash.medications.created'));
    }

    public function edit(Medication $medication): Response
    {
        $this->authorize('update', $medication);

        return Inertia::render('Medications/Form', [
            'medication' => $medication,
            'dose_form_options' => DoseForm::values(),
        ]);
    }

    public function update(UpdateMedicationRequest $request, Medication $medication): RedirectResponse
    {
        $this->authorize('update', $medication);

        $medication->update($request->validated());

        return redirect()->route('medications.index')
            ->with('success', __('flash.medications.updated'));
    }

    public function destroy(Medication $medication): RedirectResponse
    {
        $this->authorize('delete', $medication);

        $medication->delete();

        return redirect()->route('medications.index')
            ->with('success', __('flash.medications.deleted'));
    }
}
