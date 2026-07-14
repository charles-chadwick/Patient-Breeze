<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePatientLabResultRequest extends FormRequest
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
            'lab_order_id' => ['required', 'integer', 'exists:lab_orders,id'],
            // Any scalar result: numeric, threshold, qualitative ("Negative"), or boolean.
            'value' => ['required', 'string', 'max:255'],
            'unit' => ['nullable', 'string', 'max:50'],
            'collected_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
