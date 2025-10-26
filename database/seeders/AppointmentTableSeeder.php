<?php

namespace Database\Seeders;

use App\Enums\AppointmentStatus;
use App\Enums\AppointmentType;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AppointmentTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * @throws \Exception
     */
    public function run() : void
    {
        $random_data = new FilterData();
        DB::table('appointments')
            ->truncate();
//        DB::table('appointments_users')
//            ->truncate();

        foreach (Patient::all() as $patient) {

            for ($i = 0 ; $i <= rand(0, 30) ; $i++) {

                $start = fake()
                    ->dateTimeBetween($patient->created_at->addDay(), '1 year')
                    ->setTime(rand(8, 17), fake()->randomElement([
                        0,
                        15,
                        30,
                        45
                    ]));

                $end = Carbon::parse($start)
                    ->addMinutes(fake()->randomElement([
                        15,
                        30,
                        45
                    ]));

                $user = User::where('role', '!=', 'Super Admin')
                    ->inRandomOrder()
                    ->first();

                $status = fake()->randomElement(AppointmentStatus::cases());
                if ($start <= now() && $status == AppointmentStatus::Confirmed) {
                    $status = AppointmentStatus::Completed;
                }


                $created_at = fake()->dateTimeBetween($patient->created_at, Carbon::parse($start));

                $appointment = Appointment::factory()
                    ->create([
                        'patient_id'  => $patient->id,
                        'start'       => $start,
                        'end'         => $end,
                        'created_at'  => $created_at,
                        'updated_at'  => $created_at,
                        'created_by_id'  => $user->id,
                        'updated_by_id'  => $user->id,
                        'title'       => $random_data->randomData(1),
                        'description' => nl2br($random_data->randomData(rand(2, 20), false, 100)),
                        'type'        => fake()->randomElement(AppointmentType::cases()),
                        'status'      => $status,
                    ]);

//                $appointment->users()
//                    ->attach($user->id);
//
//                $appointment->users()
//                    ->attach(User::staff()
//                        ->where('id', '!=', $user->id)
//                        ->inRandomOrder()
//                        ->limit(rand(0, 3))
//                        ->pluck('id')
//                        ->toArray());

            }


        }
    }
}