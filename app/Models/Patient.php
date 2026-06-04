<?php

namespace App\Models;

use App\Enums\GenderAtBirth;
use App\Enums\GenderIdentity;
use App\Models\Concerns\Searchable;
use App\Models\Concerns\Sortable;
use Database\Factories\PatientFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Patient extends Authenticatable implements HasMedia
{
    /** @use HasFactory<PatientFactory> */
    use HasFactory, InteractsWithMedia, LogsActivity, Searchable, SoftDeletes, Sortable;

    protected $fillable = [
        'prefix',
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'email',
        'password',
        'mrn',
        'date_of_birth',
        'gender_at_birth',
        'gender_identity',
        'blood_type',
    ];

    /** @var array<int, string> */
    protected $hidden = ['password', 'remember_token'];

    /** @var array<int, string> */
    protected $appends = ['avatar_url'];

    protected function searchableFields(): array
    {
        return [
            'first_name',
            'last_name',
            'email',
            'mrn',
        ];
    }

    protected function sortableFields(): array
    {
        return [
            'last_name' => 'last_name',
            'first_name' => 'first_name',
            'date_of_birth' => 'date_of_birth',
            'blood_type' => 'blood_type',
        ];
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'patient_id');
    }

    public function contacts(): MorphMany
    {
        return $this->morphMany(Contact::class, 'contactable');
    }

    public function discussions(): MorphMany
    {
        return $this->morphMany(Discussion::class, 'discussionable');
    }

    public function portalNotifications(): HasMany
    {
        return $this->hasMany(PortalNotification::class);
    }

    public static function generateMrn(): string
    {
        $max = static::withTrashed()->lockForUpdate()->max('mrn');
        $number = $max ? ((int) substr($max, 4)) + 1 : 1;

        return 'MRN-'.str_pad((string) $number, 7, '0', STR_PAD_LEFT);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('avatar')
            ->width(400)
            ->height(400)
            ->sharpen(10)
            ->nonQueued();
    }

    public function getAvatarUrlAttribute(): string
    {
        if ($this->relationLoaded('media')) {
            return $this->getFirstMediaUrl('avatar')
                ?: asset('storage/default-avatar.png');
        }

        return asset('storage/default-avatar.png');
    }

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'email_verified_at' => 'datetime',
            'date_of_birth' => 'date',
            'gender_at_birth' => GenderAtBirth::class,
            'gender_identity' => GenderIdentity::class,
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnlyDirty()->logOnly([
            'prefix', 'first_name', 'middle_name', 'last_name', 'suffix',
            'email', 'mrn', 'date_of_birth', 'gender_at_birth', 'gender_identity', 'blood_type',
        ]);
    }
}
