<?php

namespace App\Console\Commands;

use App\Actions\AssignSimpsonsAvatar;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('avatars:assign-simpsons {--user= : ID of a specific user to process}')]
#[Description('Search for each user\'s Simpsons avatar, download, cache, and store it in the media table')]
class AssignSimpsonsAvatars extends Command
{
    public function handle(AssignSimpsonsAvatar $action): int
    {
        $users = $this->option('user')
            ? User::with('media')->where('id', $this->option('user'))->get()
            : User::with('media')->get();

        if ($users->isEmpty()) {
            $this->warn('No users found.');

            return self::FAILURE;
        }

        $assigned = 0;

        foreach ($users as $user) {
            $name = "{$user->first_name} {$user->last_name}";

            if ($action->execute($user)) {
                $this->info("✓ {$name}");
                $assigned++;
            } else {
                $this->line("  {$name} — no Simpsons avatar found");
            }
        }

        $this->newLine();
        $this->info("Done. Assigned {$assigned} avatar(s).");

        return self::SUCCESS;
    }
}
