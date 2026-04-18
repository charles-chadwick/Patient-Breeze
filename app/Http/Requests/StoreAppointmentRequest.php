<?php

namespace App\Http\Requests;

use App\Enums\AppointmentRole;
use App\Enums\AppointmentStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreAppointmentRequest extends FormRequest
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
            'date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'status' => ['required', Rule::in(array_column(AppointmentStatus::cases(), 'value'))],
            'reason' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'staff' => ['required', 'array', 'min:1'],
            'staff.*.user_id' => ['required', 'exists:users,id'],
            'staff.*.role' => ['required', Rule::in(array_column(AppointmentRole::cases(), 'value'))],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $primaryCount = collect($this->input('staff', []))
                ->filter(fn ($s) => ($s['role'] ?? '') === AppointmentRole::Primary->value)
                ->count();

            if ($primaryCount !== 1) {
                $validator->errors()->add('staff', 'Exactly one staff member must be assigned as Primary.');
            }
        });
    }
}
