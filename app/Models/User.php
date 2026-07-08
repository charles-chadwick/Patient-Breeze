<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Models\Concerns\Filterable;
use App\Models\Concerns\HasListing;
use App\Models\Concerns\Searchable;
use App\Models\Concerns\Sortable;
use App\Models\Concerns\TwoFactorAuthenticatable;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Notifications\Notifiable;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['first_name', 'middle_name', 'last_name', 'prefix', 'suffix', 'email', 'password'])]
#[Hidden(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'])]
class User extends Authenticatable implements HasMedia
{
    /** @use HasFactory<UserFactory> */
    use Filterable, HasFactory, HasListing, HasRoles, InteractsWithMedia, LogsActivity, Notifiable, Searchable, SoftDeletes, Sortable, TwoFactorAuthenticatable;

    /** @var array<int, string> */
    protected $appends = ['avatar_url'];

    public function appointments(): BelongsToMany
    {
        return $this->belongsToMany(Appointment::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    public function contacts(): MorphMany
    {
        return $this->morphMany(Contact::class, 'contactable');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function scopeStaff($query)
    {
        return $query->whereDoesntHave('roles', fn ($query) => $query->where('name', UserRole::SuperAdmin->value));
    }

    /**
     * Build the paginated user listing and its resolved query parameters.
     *
     * @return array{users: LengthAwarePaginator, search: string, sort_by: string, direction: string}
     */
    public function scopeListing(Builder $query, Request $request): array
    {
        return $this->paginatedListing($query->with(['media', 'roles']), $request, 'users');
    }

    /**
     * Resolve the assignable-user picker results for the given base query,
     * shaped for the frontend provider/participant pickers.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function scopeForPicker(Builder $query, string $search): Collection
    {
        return $query
            ->when($search !== '', fn (Builder $query) => $query->withSearch($search))
            ->with(['media' => fn ($query) => $query->where('collection_name', 'avatar')])
            ->orderBy('last_name')
            ->limit(20)
            ->get(['id', 'first_name', 'last_name'])
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'avatar_url' => $user->avatar_url,
            ]);
    }

    /**
     * Paginate this user's appointments, optionally filtered by a
     * reason/patient-name search term.
     */
    public function paginatedAppointments(string $search): LengthAwarePaginator
    {
        return $this->appointments()
            ->with(['patient.media'])
            ->when($search !== '', fn (Builder $query) => $query->matchingReasonOrPatientName($search))
            ->orderBy('date', 'desc')
            ->paginate(10)
            ->withQueryString();
    }

    protected function searchableFields(): array
    {
        return ['first_name', 'last_name', 'email'];
    }

    protected function filterableFields(): array
    {
        return ['roles' => 'roles.name'];
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
                ?: asset('storage/default-avatar.png');
        }

        return asset('storage/default-avatar.png');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnlyDirty()->logFillable();
    }
}
