<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleAndPermissionSeeder::class,
            UserSeeder::class,
            PatientSeeder::class,
            AppointmentSeeder::class,
            MedicationSeeder::class,
            DiagnosisSeeder::class,
            AllergenSeeder::class,
            VaccineSeeder::class,
            InsuranceCompanySeeder::class,
            LabOrderSeeder::class,
            LabPanelSeeder::class,
            LabReferenceRangeSeeder::class,
            EncounterNoteSeeder::class,
            NoteSeeder::class,
            ContactSeeder::class,
        ]);
    }
}
