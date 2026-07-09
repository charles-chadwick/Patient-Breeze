<?php

namespace App\Http\Requests;

use App\Enums\SettingKey;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Each submitted key must be a known setting whose value is one of that
     * setting's allowed options.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'settings' => ['required', 'array'],
        ];

        foreach (SettingKey::cases() as $setting_key) {
            $rules['settings.'.$setting_key->value] = ['sometimes', 'required', 'string', Rule::in($setting_key->options())];
        }

        return $rules;
    }
}
