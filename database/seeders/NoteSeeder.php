<?php

namespace Database\Seeders;

use App\Enums\NoteType;
use App\Models\Note;
use App\Models\Patient;
use Illuminate\Database\Seeder;

class NoteSeeder extends Seeder
{
    /**
     * The largest number of notes generated for a single patient.
     */
    private const int MAX_NOTES_PER_PATIENT = 4;

    /**
     * Give each existing patient a handful of backdated notes, with titles and
     * rich-text bodies drawn from Rick and Morty dialogue.
     */
    public function run(): void
    {
        Patient::select(['id', 'created_at'])->get()->each(function (Patient $patient): void {
            $note_count = random_int(0, self::MAX_NOTES_PER_PATIENT);

            for ($created = 0; $created < $note_count; $created++) {
                $created_at = fake()->dateTimeBetween($patient->created_at, 'now');

                Note::factory()->for($patient, 'notable')->create([
                    'type' => fake()->randomElement(NoteType::cases()),
                    'title' => RickAndMortyDialogue::next(),
                    'content' => RickAndMortyDialogue::censoredHtml(2, 5),
                    'created_at' => $created_at,
                    'updated_at' => $created_at,
                ]);
            }
        });
    }
}
