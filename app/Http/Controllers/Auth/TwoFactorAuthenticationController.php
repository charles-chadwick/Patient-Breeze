<?php

namespace App\Http\Controllers\Auth;

use App\Enums\SettingKey;
use App\Http\Controllers\Concerns\ManagesTwoFactorAuthentication;
use Illuminate\Http\Request;

class TwoFactorAuthenticationController extends ManagesTwoFactorAuthentication
{
    protected function guardName(): string
    {
        return 'web';
    }

    protected function settingsComponent(): string
    {
        return 'Settings/Index';
    }

    protected function passwordConfirmationRoute(): string
    {
        return 'password.confirm';
    }

    /**
     * Surface the staff member's stored preferences and each setting's options.
     *
     * @return array<string, mixed>
     */
    protected function additionalSettingsProps(Request $request): array
    {
        return [
            'preferences' => $request->user()->resolvedSettings(),
            'preference_options' => collect(SettingKey::cases())
                ->mapWithKeys(fn (SettingKey $key): array => [$key->value => $key->options()])
                ->all(),
        ];
    }
}
