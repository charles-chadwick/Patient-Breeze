<?php

namespace App\Http\Requests;

use App\Enums\BodyPosition;
use App\Enums\OxygenDelivery;
use App\Enums\TemperatureSite;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePatientVitalsRequest extends FormRequest
{
    /**
     * The measurement fields, at least one of which must be present for a set to
     * be worth recording.
     *
     * @var list<string>
     */
    private const MEASUREMENT_FIELDS = [
        'systolic',
        'diastolic',
        'heart_rate',
        'respiratory_rate',
        'temperature',
        'oxygen_saturation',
        'weight',
        'height',
        'pain_score',
    ];

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
            'measured_at' => ['required', 'date'],
            'appointment_id' => ['nullable', 'integer', Rule::exists('appointments', 'id')],
            'recorded_by' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'systolic' => ['nullable', 'integer', 'min:0', 'max:300'],
            'diastolic' => ['nullable', 'integer', 'min:0', 'max:200'],
            'position' => ['nullable', Rule::in(BodyPosition::values())],
            'heart_rate' => ['nullable', 'integer', 'min:0', 'max:400'],
            'respiratory_rate' => ['nullable', 'integer', 'min:0', 'max:120'],
            'temperature' => ['nullable', 'numeric', 'min:20', 'max:45'],
            'temperature_site' => ['nullable', Rule::in(TemperatureSite::values())],
            'oxygen_saturation' => ['nullable', 'integer', 'min:0', 'max:100'],
            'oxygen_delivery' => ['nullable', Rule::in(OxygenDelivery::values())],
            'weight' => ['nullable', 'numeric', 'min:0', 'max:700'],
            'height' => ['nullable', 'numeric', 'min:0', 'max:300'],
            'pain_score' => ['nullable', 'integer', 'min:0', 'max:10'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $has_measurement = collect(self::MEASUREMENT_FIELDS)
                ->contains(fn (string $field): bool => $this->filled($field));

            if (! $has_measurement) {
                $validator->errors()->add('measured_at', __('validation.vitals.at_least_one'));
            }
        });
    }
}
