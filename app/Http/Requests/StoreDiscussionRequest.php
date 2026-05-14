<?php

namespace App\Http\Requests;

use App\Enums\DiscussionType;
use App\Models\Patient;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDiscussionRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(DiscussionType::class)],
            'discussionable_type' => ['required', 'string', Rule::in([Patient::class])],
            'discussionable_id' => ['required', 'integer'],
            'participant_ids' => ['nullable', 'array'],
            'participant_ids.*' => ['integer', Rule::exists('users', 'id')],
        ];
    }
}
