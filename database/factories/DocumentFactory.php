<?php

namespace Database\Factories;

use App\Enums\DocumentType;
use App\Models\Document;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    protected $model = Document::class;

    public function definition(): array
    {
        return [
            'type' => fake()->randomElement(DocumentType::cases()),
            'name' => fake()->words(3, true),
            'document_date' => fake()->optional()->date(),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
