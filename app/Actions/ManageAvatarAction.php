<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Http\UploadedFile;

class ManageAvatarAction
{
    public function execute(User $user, ?UploadedFile $file, bool $remove): void
    {
        if ($remove) {
            $user->clearMediaCollection('avatar');
        } elseif ($file) {
            $user->addMedia($file)->toMediaCollection('avatar');
        }
    }
}
