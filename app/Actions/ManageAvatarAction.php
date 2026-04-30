<?php

namespace App\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;

class ManageAvatarAction
{
    /**
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
     */
    public function execute(Model&HasMedia $model, ?UploadedFile $file, bool $remove): void
    {
        if ($remove) {
            $model->clearMediaCollection('avatar');
        } elseif ($file) {
            $model->addMedia($file)->toMediaCollection('avatar');
        }
    }
}
