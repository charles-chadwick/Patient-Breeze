<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVaccineRequest;
use App\Http\Requests\UpdateVaccineRequest;
use App\Models\Vaccine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VaccineController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Vaccine::class);

        return Inertia::render('Vaccines/Index', Vaccine::listing($request));
    }

    /**
     * Search the vaccine catalog for the "record vaccine" modal.
     */
    public function search(Request $request): JsonResponse
    {
        $search = $request->string('search')->trim()->toString();

        return response()->json(['vaccines' => Vaccine::searchCatalog($search)]);
    }

    public function create(): Response
    {
        $this->authorize('create', Vaccine::class);

        return Inertia::render('Vaccines/Form');
    }

    public function store(StoreVaccineRequest $request): RedirectResponse
    {
        $this->authorize('create', Vaccine::class);

        Vaccine::create($request->validated());

        return redirect()->route('vaccines.index')
            ->with('success', __('flash.vaccine_catalog.created'));
    }

    public function edit(Vaccine $vaccine): Response
    {
        $this->authorize('update', $vaccine);

        return Inertia::render('Vaccines/Form', [
            'vaccine' => $vaccine,
        ]);
    }

    public function update(UpdateVaccineRequest $request, Vaccine $vaccine): RedirectResponse
    {
        $this->authorize('update', $vaccine);

        $vaccine->update($request->validated());

        return redirect()->route('vaccines.index')
            ->with('success', __('flash.vaccine_catalog.updated'));
    }

    public function destroy(Vaccine $vaccine): RedirectResponse
    {
        $this->authorize('delete', $vaccine);

        $vaccine->delete();

        return redirect()->route('vaccines.index')
            ->with('success', __('flash.vaccine_catalog.deleted'));
    }
}
