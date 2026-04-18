<?php

namespace Database\Seeders;

use App\Actions\AssignSimpsonsAvatar;
use App\Models\User;
use Illuminate\Database\Seeder;

class SimpsonsAvatarSeeder extends Seeder
{
    public function run(): void
    {
        $action = new AssignSimpsonsAvatar;

        User::with('media')->chunkById(100, function ($users) use ($action): void {
            foreach ($users as $user) {
                if ($action->execute($user)) {
                    $this->command->info("Assigned avatar: {$user->first_name} {$user->last_name}");
                }
            }
        });
    }
}
