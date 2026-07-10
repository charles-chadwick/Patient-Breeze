<?php

namespace Database\Seeders;

use App\Enums\ContactType;
use App\Models\Contact;
use App\Models\Patient;
use Illuminate\Database\Seeder;

class ContactSeeder extends Seeder
{
    /**
     * The largest number of contacts generated for a single patient.
     */
    private const int MAX_CONTACTS_PER_PATIENT = 3;

    /**
     * Give each existing patient a few backdated contacts, named after Rick and
     * Morty characters. Phone, address, and ROI flag come from the factory.
     */
    public function run(): void
    {
        Patient::select(['id', 'created_at'])->get()->each(function (Patient $patient): void {
            $contact_count = random_int(0, self::MAX_CONTACTS_PER_PATIENT);

            for ($created = 0; $created < $contact_count; $created++) {
                $character = RickAndMortyCharacters::next();
                $created_at = fake()->dateTimeBetween($patient->created_at, 'now');

                Contact::factory()->for($patient, 'contactable')->create([
                    'name' => trim("{$character['first_name']} {$character['last_name']}"),
                    'type' => fake()->randomElement(ContactType::cases()),
                    'created_at' => $created_at,
                    'updated_at' => $created_at,
                ]);
            }
        });
    }
}
