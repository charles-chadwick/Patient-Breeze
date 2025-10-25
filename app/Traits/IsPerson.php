<?php

/** @noinspection PhpUndefinedMethodInspection */
/** @noinspection PhpUndefinedFieldInspection */

/** @noinspection PhpUnnecessaryCurlyVarSyntaxInspection */

namespace App\Traits;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

trait IsPerson
{
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getInitialsAttribute(): string
    {
        return $this->first_name[0].$this->last_name[0];
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('avatars')
            ->fit(Fit::Contain, 300, 300)
            ->nonQueued();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatars')
            ->singleFile();
    }

    public function avatar(): Attribute
    {
        // check to make sure it exists, default if it doesn't
        if (! file_exists($this->getFirstMediaPath('avatars'))) {
            $image = null;
        } else {
            $image = url(str($this->getFirstMediaUrl('avatars'))->replace('localhost', 'localhost:8080'));
        }

        return Attribute::make(
            get: function () use ($image) {
                return $image;
            }
        );
    }
}
