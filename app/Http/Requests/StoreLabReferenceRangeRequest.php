<?php

namespace App\Http\Requests;

use App\Enums\GenderAtBirth;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLabReferenceRangeRequest extends FormRequest
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
            'gender_at_birth' => ['nullable', Rule::in(array_column(GenderAtBirth::cases(), 'value'))],
            'min_age' => ['nullable', 'integer', 'min:0', 'max:150'],
            'max_age' => ['nullable', 'integer', 'min:0', 'max:150'],
            // A range needs at least one bound; either may be numeric or qualitative.
            'low_value' => ['nullable', 'required_without:high_value', 'string', 'max:255'],
            'high_value' => ['nullable', 'required_without:low_value', 'string', 'max:255'],
            'unit' => ['required', 'string', 'max:50'],
        ];
    }
}
