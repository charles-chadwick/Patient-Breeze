<?php

namespace Database\Factories;

use App\Enums\ContactType;
use App\Models\Contact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contact>
 */
class ContactFactory extends Factory
{
    protected $model = Contact::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'type' => fake()->randomElement(ContactType::cases()),
            'phone' => fake()->optional()->phoneNumber(),
            'street_address' => fake()->optional()->streetAddress(),
            'roi' => fake()->optional()->boolean(),
        ];
    }
}
