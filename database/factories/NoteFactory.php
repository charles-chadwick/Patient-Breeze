<?php

namespace Database\Factories;

use App\Enums\NoteType;
use App\Models\Note;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Note>
 */
class NoteFactory extends Factory
{
    protected $model = Note::class;

    public function definition(): array
    {
        return [
            'type' => fake()->randomElement(NoteType::cases()),
            'title' => fake()->sentence(4),
            'content' => '<p>'.fake()->paragraph().'</p>',
        ];
    }
}
