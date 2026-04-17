<?php

namespace Database\Seeders;

use App\Models\Appointment;
use Illuminate\Database\Seeder;

class AppointmentSeeder extends Seeder
{
    public function run(): void
    {
        Appointment::factory(100)->create();

        Appointment::factory(20)->withProviders(2)->create();
    }
}
