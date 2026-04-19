<?php

namespace App\Http\Requests;

use App\Enums\BloodType;
use App\Enums\GenderAtBirth;
use App\Enums\GenderIdentity;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePatientRequest extends FormRequest
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
            'prefix' => ['nullable', 'string', 'max:10'],
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'suffix' => ['nullable', 'string', 'max:20'],
            'email' => ['required', 'email', 'unique:users,email'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'gender_at_birth' => ['required', Rule::in(array_column(GenderAtBirth::cases(), 'value'))],
            'gender_identity' => ['nullable', Rule::in(array_column(GenderIdentity::cases(), 'value'))],
            'blood_type' => ['nullable', Rule::in(array_column(BloodType::cases(), 'value'))],
            'avatar' => ['nullable', 'image', 'max:2048'],
            'remove_avatar' => ['nullable', 'boolean'],
        ];
    }
}
