<?php

namespace App\Http\Requests;

use App\Enums\ContactType;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContactRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(ContactType::class)],
            'phone' => ['nullable', 'string', 'max:255'],
            'street_address' => ['nullable', 'string', 'max:255'],
            'roi' => ['nullable', 'boolean'],
            'contactable_type' => ['required', 'string', Rule::in([Patient::class, User::class])],
            'contactable_id' => ['required', 'integer'],
        ];
    }
}
