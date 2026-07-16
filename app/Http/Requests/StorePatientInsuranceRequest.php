<?php

namespace App\Http\Requests;

use App\Enums\InsurancePlanType;
use App\Enums\InsurancePriority;
use App\Enums\SubscriberRelationship;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePatientInsuranceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'insurance_company_id' => ['required', 'integer', Rule::exists('insurance_companies', 'id')],
            'member_id' => ['required', 'string', 'max:100'],
            'group_number' => ['nullable', 'string', 'max:100'],
            'plan_type' => ['nullable', Rule::in(InsurancePlanType::values())],
            'priority' => ['required', Rule::in(InsurancePriority::values())],
            'subscriber_name' => ['nullable', 'string', 'max:255'],
            'relationship_to_subscriber' => ['required', Rule::in(SubscriberRelationship::values())],
            'effective_on' => ['nullable', 'date'],
            'terminates_on' => ['nullable', 'date', 'after_or_equal:effective_on'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
