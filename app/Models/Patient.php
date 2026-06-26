<?php

namespace App\Models;

use App\Enums\DiscussionType;
use App\Enums\GenderAtBirth;
use App\Enums\GenderIdentity;
use App\Models\Concerns\HasListing;
use App\Models\Concerns\Searchable;
use App\Models\Concerns\Sortable;
use Database\Factories\PatientFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Patient extends Authenticatable implements HasMedia
{
    /** @use HasFactory<PatientFactory> */
    use HasFactory, HasListing, InteractsWithMedia, LogsActivity, Searchable, SoftDeletes, Sortable;

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

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function portalNotifications(): HasMany
    {
        return $this->hasMany(PortalNotification::class);
    }

    /**
     * Build this patient's portal message threads, shaped for the portal UI.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function portalMessageThreads(): Collection
    {
        return $this->discussions()
            ->where('type', DiscussionType::PortalMessage)
            ->with([
                'posts' => fn ($query) => $query->orderBy('created_at'),
                'posts.user:id,first_name,last_name',
                'posts.patient:id,first_name,last_name',
            ])
            ->latest()
            ->get()
            ->map(fn (Discussion $discussion) => [
                'id' => $discussion->id,
                'title' => $discussion->title,
                'created_at' => $discussion->created_at,
                'posts' => $discussion->posts->map(fn ($post) => [
                    'id' => $post->id,
                    'content' => $post->content,
                    'created_at' => $post->created_at,
                    'from_patient' => $post->patient_id !== null,
                    'author_name' => $post->patient_id
                        ? 'You'
                        : trim(($post->user?->first_name ?? 'Staff').' '.($post->user?->last_name ?? '')),
                ]),
            ]);
    }

    /**
     * Build the paginated patient listing and its resolved query parameters.
     *
     * @return array{patients: LengthAwarePaginator, search: string, sort_by: string, direction: string}
     */
    public function scopeListing(Builder $query, Request $request): array
    {
        return $this->paginatedListing(
            $query->select('id', 'first_name', 'last_name', 'mrn', 'gender_at_birth', 'gender_identity', 'blood_type', 'date_of_birth', 'created_at', 'updated_at')->with('media'),
            $request,
            'patients',
        );
    }

    public function scopeMatchingName(Builder $query, string $search): void
    {
        $query->where(fn (Builder $query) => $query
            ->where('first_name', 'like', "%{$search}%")
            ->orWhere('last_name', 'like', "%{$search}%")
        );
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
