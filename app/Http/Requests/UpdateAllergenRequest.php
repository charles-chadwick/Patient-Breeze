<?php

namespace App\Http\Requests;

use App\Enums\AllergenCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAllergenRequest extends FormRequest
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
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('allergens', 'name')
                    ->where('category', $this->input('category'))
                    ->ignore($this->route('allergen')),
            ],
            'category' => ['required', Rule::in(AllergenCategory::values())],
        ];
    }
}
