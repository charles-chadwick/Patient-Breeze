<?php

namespace App\Http\Controllers;

use App\Enums\SettingKey;
use App\Http\Requests\UpdateUserSettingsRequest;
use Illuminate\Http\RedirectResponse;

class SettingController extends Controller
{
    /**
     * Persist the authenticated user's preference settings.
     */
    public function update(UpdateUserSettingsRequest $request): RedirectResponse
    {
        $user = $request->user();

        foreach ($request->validated('settings') as $key => $value) {
            $user->setSetting(SettingKey::from($key), $value);
        }

        return back()->with('success', __('flash.settings.updated'));
    }
}
