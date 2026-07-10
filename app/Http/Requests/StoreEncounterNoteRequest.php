<?php

namespace App\Http\Requests;

use App\Enums\EncounterNoteType;
use App\Models\Patient;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEncounterNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Patient $patient */
        $patient = $this->route('patient');

        return [
            'type' => ['required', Rule::enum(EncounterNoteType::class)],
            'author_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'encounter_date' => ['required', 'date'],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'appointment_id' => [
                'nullable',
                'integer',
                Rule::exists('appointments', 'id')->where('patient_id', $patient->id),
            ],
        ];
    }
}
