<?php

namespace App\Http\Requests;

use App\Enums\DoseForm;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMedicationRequest extends FormRequest
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
            'type' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'dosage' => ['required', 'string', 'max:255'],
            'dose_form' => ['required', Rule::in(DoseForm::values())],
            'ndc' => ['required', 'string', 'max:255', Rule::unique('medications', 'ndc')],
        ];
    }
}
