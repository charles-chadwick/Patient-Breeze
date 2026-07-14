<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLabReferenceRangeRequest;
use App\Http\Requests\UpdateLabReferenceRangeRequest;
use App\Models\LabOrder;
use App\Models\LabReferenceRange;
use Illuminate\Http\RedirectResponse;

class LabReferenceRangeController extends Controller
{
    public function store(StoreLabReferenceRangeRequest $request, LabOrder $labOrder): RedirectResponse
    {
        $this->authorize('update', $labOrder);

        $labOrder->referenceRanges()->create($request->validated());

        return redirect()->route('lab-orders.edit', $labOrder)
            ->with('success', __('flash.lab_reference_ranges.created'));
    }

    public function update(UpdateLabReferenceRangeRequest $request, LabOrder $labOrder, LabReferenceRange $referenceRange): RedirectResponse
    {
        $this->authorize('update', $labOrder);

        $referenceRange->update($request->validated());

        return redirect()->route('lab-orders.edit', $labOrder)
            ->with('success', __('flash.lab_reference_ranges.updated'));
    }

    public function destroy(LabOrder $labOrder, LabReferenceRange $referenceRange): RedirectResponse
    {
        $this->authorize('update', $labOrder);

        $referenceRange->delete();

        return redirect()->route('lab-orders.edit', $labOrder)
            ->with('success', __('flash.lab_reference_ranges.deleted'));
    }
}
