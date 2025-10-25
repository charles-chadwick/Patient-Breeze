<?php

/** @noinspection PhpUndefinedFieldInspection */
/** @noinspection PhpMultipleClassDeclarationsInspection */
/** @noinspection PhpUnused */

namespace App\Models;

use App\Traits\IsPerson;
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Notifications\Notifiable;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class User extends Base implements AuthenticatableContract, AuthorizableContract, CanResetPasswordContract
{
    use Authenticatable, Authorizable, CanResetPassword, MustVerifyEmail;
    use HasFactory, Notifiable;
    use IsPerson;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'role',
        'prefix',
        'first_name',
        'last_name',
        'suffix',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

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
