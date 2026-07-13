<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDiagnosisRequest extends FormRequest
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
            'diagnosis' => ['required', 'string', 'max:255'],
            'icd10_code' => ['required', 'string', 'max:255', Rule::unique('diagnoses', 'icd10_code')],
        ];
    }
}
