<?php

namespace App\Http\Requests\Portal;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Recipients are optional, but any chosen recipient must be a user who has
     * opted in to receiving directed portal messages.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:5000'],
            'recipient_ids' => ['nullable', 'array'],
            'recipient_ids.*' => [
                'integer',
                Rule::exists('users', 'id'),
                function (string $attribute, mixed $value, callable $fail): void {
                    if (! User::receivingPortalMessages()->whereKey($value)->exists()) {
                        $fail(__('portal.messages.recipient_unavailable'));
                    }
                },
            ],
        ];
    }
}
