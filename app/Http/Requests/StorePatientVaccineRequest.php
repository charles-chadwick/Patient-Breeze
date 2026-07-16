<?php

namespace App\Http\Requests;

use App\Enums\VaccineRoute;
use App\Enums\VaccineSite;
use App\Enums\VaccineStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePatientVaccineRequest extends FormRequest
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
            'vaccine' => ['required', 'string', 'max:255'],
            'cvx_code' => ['nullable', 'string', 'max:10'],
            'administered_on' => ['required', 'date'],
            'dose_number' => ['nullable', 'integer', 'min:1', 'max:10'],
            'status' => ['required', Rule::in(VaccineStatus::values())],
            'route' => ['nullable', Rule::in(VaccineRoute::values())],
            'site' => ['nullable', Rule::in(VaccineSite::values())],
            'dose_amount' => ['nullable', 'string', 'max:50'],
            'manufacturer' => ['nullable', 'string', 'max:255'],
            'lot_number' => ['nullable', 'string', 'max:100'],
            'expires_on' => ['nullable', 'date'],
            'administered_by' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
