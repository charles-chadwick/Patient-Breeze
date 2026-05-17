<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Models\Concerns\Searchable;
use App\Models\Concerns\Sortable;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['first_name', 'middle_name', 'last_name', 'prefix', 'suffix', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements HasMedia
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, InteractsWithMedia, LogsActivity, Notifiable, Searchable, SoftDeletes, Sortable;

    /** @var array<int, string> */
    protected $appends = ['avatar_url'];

    public function contacts(): MorphMany
    {
        return $this->morphMany(Contact::class, 'contactable');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function appointments(): BelongsToMany
    {
        return $this->belongsToMany(Appointment::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function scopeStaff($query)
    {
        return $query->whereDoesntHave('roles', fn ($q) => $q->where('name', UserRole::SuperAdmin->value));
    }

    protected function searchableFields(): array
    {
        return ['first_name', 'last_name', 'email'];
    }

    protected function sortableFields(): array
    {
        return [
            'last_name' => 'last_name',
            'first_name' => 'first_name',
            'email' => 'email',
        ];
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

    public static function identityData(array $validated): array
    {
        return [
            'prefix' => $validated['prefix'] ?? '',
            'first_name' => $validated['first_name'],
            'middle_name' => $validated['middle_name'] ?? '',
            'last_name' => $validated['last_name'],
            'suffix' => $validated['suffix'] ?? '',
            'email' => $validated['email'],
        ];
    }

    public function getAvatarUrlAttribute(): string
    {
        if ($this->relationLoaded('media')) {
            return $this->getFirstMediaUrl('avatar')
                ?: "https://i.pravatar.cc/80?u={$this->email}";
        }

        return "https://i.pravatar.cc/80?u={$this->email}";
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnlyDirty()->logFillable();
    }
}
