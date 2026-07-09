<?php

namespace Database\Factories;

use App\Enums\SettingKey;
use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserSetting>
 */
class UserSettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $setting_key = fake()->randomElement(SettingKey::cases());

        return [
            'user_id' => User::factory(),
            'key' => $setting_key,
            'value' => fake()->randomElement($setting_key->options()),
        ];
    }
}
