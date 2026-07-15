<?php

namespace App\Http\Controllers;

use App\Enums\AllergenCategory;
use App\Http\Requests\StoreAllergenRequest;
use App\Http\Requests\UpdateAllergenRequest;
use App\Models\Allergen;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AllergenController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Allergen::class);

        return Inertia::render('Allergens/Index', [
            ...Allergen::listing($request),
            'category_options' => AllergenCategory::values(),
        ]);
    }

    /**
     * Search the allergen catalog for the "add allergy" modal.
     */
    public function search(Request $request): JsonResponse
    {
        $search = $request->string('search')->trim()->toString();

        return response()->json(['allergens' => Allergen::searchCatalog($search)]);
    }

    public function create(): Response
    {
        $this->authorize('create', Allergen::class);

        return Inertia::render('Allergens/Form', [
            'category_options' => AllergenCategory::values(),
        ]);
    }

    public function store(StoreAllergenRequest $request): RedirectResponse
    {
        $this->authorize('create', Allergen::class);

        Allergen::create($request->validated());

        return redirect()->route('allergens.index')
            ->with('success', __('flash.allergens.created'));
    }

    public function edit(Allergen $allergen): Response
    {
        $this->authorize('update', $allergen);

        return Inertia::render('Allergens/Form', [
            'allergen' => $allergen,
            'category_options' => AllergenCategory::values(),
        ]);
    }

    public function update(UpdateAllergenRequest $request, Allergen $allergen): RedirectResponse
    {
        $this->authorize('update', $allergen);

        $allergen->update($request->validated());

        return redirect()->route('allergens.index')
            ->with('success', __('flash.allergens.updated'));
    }

    public function destroy(Allergen $allergen): RedirectResponse
    {
        $this->authorize('delete', $allergen);

        $allergen->delete();

        return redirect()->route('allergens.index')
            ->with('success', __('flash.allergens.deleted'));
    }
}
