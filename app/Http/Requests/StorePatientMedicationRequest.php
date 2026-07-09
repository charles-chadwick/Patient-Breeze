<?php

namespace App\Http\Requests;

use App\Enums\DoseForm;
use App\Enums\Frequency;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePatientMedicationRequest extends FormRequest
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
            'frequency' => ['required', Rule::in(Frequency::values())],
            'amount' => ['required', 'string', 'max:255'],
            'ndc' => ['nullable', 'string', 'max:255'],
        ];
    }
}
