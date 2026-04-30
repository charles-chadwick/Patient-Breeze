<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;

class ManageAvatarAction
{
    /**
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
     */
    public function execute(User $user, ?UploadedFile $file, bool $remove): void
    {
        if ($remove) {
            $user->clearMediaCollection('avatar');
        } elseif ($file) {
            $user->addMedia($file)->toMediaCollection('avatar');
        }
    }
}
