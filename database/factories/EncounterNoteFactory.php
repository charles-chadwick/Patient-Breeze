<?php

namespace Database\Factories;

use App\Enums\EncounterNoteStatus;
use App\Enums\EncounterNoteType;
use App\Models\EncounterNote;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EncounterNote>
 */
class EncounterNoteFactory extends Factory
{
    protected $model = EncounterNote::class;

    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'author_id' => User::factory(),
            'appointment_id' => null,
            'type' => fake()->randomElement(EncounterNoteType::cases()),
            'encounter_date' => fake()->dateTimeBetween('-1 year')->format('Y-m-d'),
            'title' => fake()->sentence(4),
            'content' => '<p>'.fake()->paragraph().'</p>',
            'status' => EncounterNoteStatus::Unsigned,
        ];
    }

    public function signed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => EncounterNoteStatus::Signed,
            'signed_by' => $attributes['author_id'] ?? User::factory(),
            'signed_at' => now(),
        ]);
    }

    public function coSigned(): static
    {
        return $this->signed()->state(fn () => [
            'status' => EncounterNoteStatus::CoSigned,
            'co_signed_by' => User::factory(),
            'co_signed_at' => now(),
        ]);
    }
}
