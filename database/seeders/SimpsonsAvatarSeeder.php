<?php

namespace Database\Seeders;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class SimpsonsAvatarSeeder extends Seeder
{
    public function run(): void
    {
        $action = new AssignSimpsonsAvatar;

        User::with('media')->chunkById(100, function (Collection $users) use ($action): void {
            foreach ($users as $user) {
                if ($action->execute($user)) {
                    $this->command->info("Assigned avatar: {$user->first_name} {$user->last_name}");
                }
            }
        });

        Patient::with('media')->chunkById(100, function (Collection $patients) use ($action): void {
            foreach ($patients as $patient) {
                if ($action->execute($patient)) {
                    $this->command->info("Assigned avatar: {$patient->first_name} {$patient->last_name}");
                }
            }
        });
    }
}
