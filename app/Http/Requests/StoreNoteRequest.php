<?php

namespace App\Http\Requests;

use App\Enums\NoteType;
use App\Models\Patient;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreNoteRequest extends FormRequest
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
        return [
            'type' => ['required', Rule::enum(NoteType::class)],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'notable_type' => ['required', 'string', Rule::in([Patient::class])],
            'notable_id' => ['required', 'integer'],
        ];
    }
}
