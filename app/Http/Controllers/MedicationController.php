<?php

namespace App\Http\Controllers;

use App\Models\Medication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MedicationController extends Controller
{
    /**
     * Search the medication catalog for the "add medication" modal.
     */
    public function search(Request $request): JsonResponse
    {
        $search = $request->string('search')->trim()->toString();

        $medications = Medication::query()
            ->when($search !== '', fn ($query) => $query->matchingSearch($search))
            ->orderBy('name')
            ->limit(20)
            ->get()
            ->map(fn (Medication $medication): array => [
                'id' => $medication->id,
                'type' => $medication->type,
                'name' => $medication->name,
                'dosage' => $medication->dosage,
                'dose_form' => $medication->dose_form->value,
                'ndc' => $medication->ndc,
            ]);

        return response()->json(['medications' => $medications]);
    }
}
