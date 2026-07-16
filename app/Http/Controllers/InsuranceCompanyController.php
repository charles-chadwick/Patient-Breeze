<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInsuranceCompanyRequest;
use App\Http\Requests\UpdateInsuranceCompanyRequest;
use App\Models\InsuranceCompany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InsuranceCompanyController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', InsuranceCompany::class);

        return Inertia::render('InsuranceCompanies/Index', InsuranceCompany::listing($request));
    }

    /**
     * Search the insurance catalog for the "add insurance" modal.
     */
    public function search(Request $request): JsonResponse
    {
        $search = $request->string('search')->trim()->toString();

        return response()->json(['insurance_companies' => InsuranceCompany::searchCatalog($search)]);
    }

    public function create(): Response
    {
        $this->authorize('create', InsuranceCompany::class);

        return Inertia::render('InsuranceCompanies/Form');
    }

    public function store(StoreInsuranceCompanyRequest $request): RedirectResponse
    {
        $this->authorize('create', InsuranceCompany::class);

        InsuranceCompany::create($request->validated());

        return redirect()->route('insurance-companies.index')
            ->with('success', __('flash.insurance_companies.created'));
    }

    public function edit(InsuranceCompany $insuranceCompany): Response
    {
        $this->authorize('update', $insuranceCompany);

        return Inertia::render('InsuranceCompanies/Form', [
            'insurance_company' => $insuranceCompany,
        ]);
    }

    public function update(UpdateInsuranceCompanyRequest $request, InsuranceCompany $insuranceCompany): RedirectResponse
    {
        $this->authorize('update', $insuranceCompany);

        $insuranceCompany->update($request->validated());

        return redirect()->route('insurance-companies.index')
            ->with('success', __('flash.insurance_companies.updated'));
    }

    public function destroy(InsuranceCompany $insuranceCompany): RedirectResponse
    {
        $this->authorize('delete', $insuranceCompany);

        $insuranceCompany->delete();

        return redirect()->route('insurance-companies.index')
            ->with('success', __('flash.insurance_companies.deleted'));
    }
}
