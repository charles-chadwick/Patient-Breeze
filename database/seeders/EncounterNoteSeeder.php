<?php

namespace Database\Seeders;

use App\Enums\EncounterNoteStatus;
use App\Enums\EncounterNoteType;
use App\Models\Appointment;
use App\Models\EncounterNote;
use App\Models\Patient;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class EncounterNoteSeeder extends Seeder
{
    /**
     * The largest number of encounter notes generated for a single patient.
     */
    private const int MAX_NOTES_PER_PATIENT = 4;

    /**
     * Odds (out of 100) that a note is linked to one of the patient's
     * appointments rather than a free-standing encounter.
     */
    private const int APPOINTMENT_LINK_CHANCE = 60;

    /**
     * Seed encounter notes for existing patients, authored and signed by the
     * seeded providers. Titles and bodies are drawn from Rick and Morty
     * dialogue, and each note is placed somewhere in its patient's lifecycle
     * with a realistic signing state.
     */
    public function run(): void
    {
        $providers = User::select(['id', 'created_at'])->get();

        if ($providers->count() < 2) {
            return;
        }

        $appointments_by_patient = Appointment::get(['id', 'patient_id', 'date'])
            ->groupBy('patient_id');

        Patient::select(['id', 'created_at'])->get()->each(
            function (Patient $patient) use ($providers, $appointments_by_patient): void {
                $appointments = $appointments_by_patient->get($patient->id, collect());
                $note_count = random_int(0, self::MAX_NOTES_PER_PATIENT);

                for ($created = 0; $created < $note_count; $created++) {
                    $this->createNote($patient, $providers, $appointments);
                }
            }
        );
    }

    /**
     * Create a single backdated encounter note for the given patient.
     *
     * @param  Collection<int, User>  $providers
     * @param  \Illuminate\Support\Collection<int, Appointment>  $appointments
     */
    private function createNote(Patient $patient, Collection $providers, \Illuminate\Support\Collection $appointments): void
    {
        $created_at = fake()->dateTimeBetween($patient->created_at, 'now');
        $author = $this->providerFor($providers, $created_at);

        $appointment = $appointments->isNotEmpty() && fake()->boolean(self::APPOINTMENT_LINK_CHANCE)
            ? $appointments->random()
            : null;

        $encounter_date = $appointment
            ? Carbon::parse($appointment->date)
            : fake()->dateTimeBetween($patient->created_at, $created_at);

        EncounterNote::factory()->create([
            'patient_id' => $patient->id,
            'author_id' => $author->id,
            'appointment_id' => $appointment?->id,
            'type' => fake()->randomElement(EncounterNoteType::cases()),
            'encounter_date' => Carbon::parse($encounter_date)->format('Y-m-d'),
            'title' => RickAndMortyDialogue::next(),
            'content' => RickAndMortyDialogue::censoredHtml(2, 5),
            'created_at' => $created_at,
            'updated_at' => $created_at,
            ...$this->signatureFor($providers, $author, $created_at),
        ]);
    }

    /**
     * Choose a random signing state for a note, wiring up the signer and
     * (occasionally) a distinct co-signer with backdated timestamps.
     *
     * @param  Collection<int, User>  $providers
     * @return array<string, mixed>
     */
    private function signatureFor(Collection $providers, User $author, DateTimeInterface $created_at): array
    {
        $roll = random_int(1, 100);

        if ($roll <= 35) {
            return ['status' => EncounterNoteStatus::Unsigned];
        }

        $signed_at = fake()->dateTimeBetween($created_at, 'now');

        if ($roll <= 75) {
            return [
                'status' => EncounterNoteStatus::Signed,
                'signed_by' => $author->id,
                'signed_at' => $signed_at,
            ];
        }

        $co_signer = $providers->where('id', '!=', $author->id)->random();

        return [
            'status' => EncounterNoteStatus::CoSigned,
            'signed_by' => $author->id,
            'signed_at' => $signed_at,
            'co_signed_by' => $co_signer->id,
            'co_signed_at' => fake()->dateTimeBetween($signed_at, 'now'),
        ];
    }

    /**
     * Attribute authorship to a provider who already existed when the note was
     * written, falling back to any provider when none predate it.
     *
     * @param  Collection<int, User>  $providers
     */
    private function providerFor(Collection $providers, DateTimeInterface $created_at): User
    {
        $eligible = $providers->filter(fn (User $provider) => $provider->created_at <= $created_at);

        return $eligible->isNotEmpty() ? $eligible->random() : $providers->random();
    }
}
