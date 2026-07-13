<?php

namespace App\Models;

use App\Contracts\LinksActivityToPatient;
use App\Enums\DiscussionType;
use App\Enums\GenderAtBirth;
use App\Enums\GenderIdentity;
use App\Models\Concerns\Filterable;
use App\Models\Concerns\HasListing;
use App\Models\Concerns\Searchable;
use App\Models\Concerns\Sortable;
use App\Models\Concerns\TwoFactorAuthenticatable;
use Database\Factories\PatientFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
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

class Patient extends Authenticatable implements HasMedia, LinksActivityToPatient
{
    /** @use HasFactory<PatientFactory> */
    use Filterable, HasFactory, HasListing, InteractsWithMedia, LogsActivity, Searchable, SoftDeletes, Sortable, TwoFactorAuthenticatable;

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
    protected $hidden = ['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'];

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

    protected function filterableFields(): array
    {
        return [];
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'patient_id');
    }

    public function appointmentRequests(): HasMany
    {
        return $this->hasMany(AppointmentRequest::class);
    }

    /**
     * Build this patient's recent appointment requests, shaped for the portal.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function portalAppointmentRequests(int $limit = 10): Collection
    {
        return $this->appointmentRequests()
            ->with('provider:id,first_name,last_name')
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (AppointmentRequest $request): array => [
                'id' => $request->id,
                'date' => $request->date->toDateString(),
                'start_time' => substr($request->start_time, 0, 5),
                'end_time' => substr($request->end_time, 0, 5),
                'reason' => $request->reason,
                'status' => $request->status->value,
                'status_label' => $request->status->label(),
                'provider' => $request->provider
                    ? $request->provider->only(['id', 'first_name', 'last_name'])
                    : null,
            ]);
    }

    public function encounterNotes(): HasMany
    {
        return $this->hasMany(EncounterNote::class);
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

    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'notable');
    }

    public function patientMedications(): HasMany
    {
        return $this->hasMany(PatientMedication::class, 'patient_id');
    }

    public function patientDiagnoses(): HasMany
    {
        return $this->hasMany(PatientDiagnosis::class, 'patient_id');
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
     * Paginate this patient's appointments for the chart, optionally filtered by
     * a reason/notes search term.
     */
    public function paginatedAppointments(string $search): LengthAwarePaginator
    {
        return $this->appointments()
            ->with(['users.media'])
            ->when($search !== '', fn (Builder $query) => $query->matchingReasonOrNotes($search))
            ->orderBy('date', 'desc')
            ->paginate(10)
            ->withQueryString();
    }

    /**
     * Load this patient's discussion threads for the chart's discussion tab.
     *
     * @return EloquentCollection<int, Discussion>
     */
    public function discussionThread(): EloquentCollection
    {
        return $this->discussions()
            ->with([
                'participants.participantable.media',
                'posts' => fn ($query) => $query->with([
                    'user:id,first_name,last_name',
                    'user.media',
                    'patient:id,first_name,last_name',
                    'patient.media',
                ])->orderBy('created_at'),
            ])
            ->latest()
            ->get();
    }

    /**
     * Fetch this patient's upcoming appointments for the portal dashboard.
     *
     * @return EloquentCollection<int, Appointment>
     */
    public function upcomingAppointments(int $limit = 5): EloquentCollection
    {
        return $this->appointments()
            ->where('date', '>=', now()->toDateString())
            ->orderBy('date')
            ->orderBy('start_time')
            ->limit($limit)
            ->get(['id', 'date', 'start_time', 'end_time', 'reason', 'status']);
    }

    /**
     * Fetch this patient's most recent discussions for the portal dashboard.
     *
     * @return EloquentCollection<int, Discussion>
     */
    public function recentDiscussions(int $limit = 3): EloquentCollection
    {
        return $this->discussions()
            ->latest()
            ->limit($limit)
            ->get(['id', 'title', 'status', 'created_at']);
    }

    /**
     * Build this patient's documents, shaped for the portal dashboard.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function portalDocuments(): Collection
    {
        return $this->documents()
            ->with(['media', 'uploader'])
            ->latest()
            ->get()
            ->map(fn (Document $document) => [
                'id' => $document->id,
                'type_label' => $document->type->label(),
                'name' => $document->name,
                'document_date' => $document->document_date?->toDateString(),
                'notes' => $document->notes,
                'created_at' => $document->created_at->toDateString(),
                'download_url' => route('portal.documents.download', $document->id),
                'can_delete' => $document->uploader_type === self::class && $document->uploader_id === $this->id,
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
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnlyDirty()->logOnly([
            'prefix', 'first_name', 'middle_name', 'last_name', 'suffix',
            'email', 'mrn', 'date_of_birth', 'gender_at_birth', 'gender_identity', 'blood_type',
        ]);
    }

    public function auditPatientId(): ?int
    {
        return $this->id;
    }
}
