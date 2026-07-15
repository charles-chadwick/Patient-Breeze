<?php

namespace App\Http\Requests;

use App\Enums\AllergenCategory;
use App\Enums\AllergyReaction;
use App\Enums\AllergySeverity;
use App\Enums\AllergyStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePatientAllergyRequest extends FormRequest
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
            'allergen' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in(AllergenCategory::values())],
            'reactions' => ['required', 'array', 'min:1'],
            'reactions.*' => [Rule::in(AllergyReaction::values())],
            'severity' => ['required', Rule::in(AllergySeverity::values())],
            'status' => ['required', Rule::in(AllergyStatus::values())],
            'onset_on' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
