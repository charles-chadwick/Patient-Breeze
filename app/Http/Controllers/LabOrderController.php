<?php

namespace App\Http\Controllers;

use App\Enums\GenderAtBirth;
use App\Http\Requests\StoreLabOrderRequest;
use App\Http\Requests\UpdateLabOrderRequest;
use App\Models\LabOrder;
use App\Models\LabReferenceRange;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LabOrderController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', LabOrder::class);

        return Inertia::render('LabOrders/Index', [
            ...LabOrder::listing($request),
        ]);
    }

    /**
     * Search the lab order catalog for the "add lab order" modal.
     */
    public function search(Request $request): JsonResponse
    {
        $search = $request->string('search')->trim()->toString();

        return response()->json(['lab_orders' => LabOrder::searchCatalog($search)]);
    }

    public function create(): Response
    {
        $this->authorize('create', LabOrder::class);

        return Inertia::render('LabOrders/Form');
    }

    public function store(StoreLabOrderRequest $request): RedirectResponse
    {
        $this->authorize('create', LabOrder::class);

        LabOrder::create($request->validated());

        return redirect()->route('lab-orders.index')
            ->with('success', __('flash.lab_orders.created'));
    }

    public function edit(LabOrder $labOrder): Response
    {
        $this->authorize('update', $labOrder);

        $referenceRanges = $labOrder->referenceRanges()
            ->orderByRaw('gender_at_birth is null')
            ->orderBy('gender_at_birth')
            ->orderByRaw('min_age is null')
            ->orderBy('min_age')
            ->get()
            ->map(fn (LabReferenceRange $range): array => [
                'id' => $range->id,
                'gender_at_birth' => $range->gender_at_birth?->value,
                'min_age' => $range->min_age,
                'max_age' => $range->max_age,
                'low_value' => $range->getRawOriginal('low_value'),
                'high_value' => $range->getRawOriginal('high_value'),
                'unit' => $range->unit,
                'label' => $range->label(),
            ]);

        return Inertia::render('LabOrders/Form', [
            'lab_order' => $labOrder,
            'reference_ranges' => $referenceRanges,
            'gender_at_birth_options' => array_column(GenderAtBirth::cases(), 'value'),
        ]);
    }

    public function update(UpdateLabOrderRequest $request, LabOrder $labOrder): RedirectResponse
    {
        $this->authorize('update', $labOrder);

        $labOrder->update($request->validated());

        return redirect()->route('lab-orders.index')
            ->with('success', __('flash.lab_orders.updated'));
    }

    public function destroy(LabOrder $labOrder): RedirectResponse
    {
        $this->authorize('delete', $labOrder);

        $labOrder->delete();

        return redirect()->route('lab-orders.index')
            ->with('success', __('flash.lab_orders.deleted'));
    }
}
