<?php

namespace App\Models;

use App\Enums\ContactType;
use App\Models\Concerns\Searchable;
use App\Models\Concerns\Sortable;
use Database\Factories\ContactFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Contact extends Model implements HasMedia
{
    /** @use HasFactory<ContactFactory> */
    use HasFactory, InteractsWithMedia, LogsActivity, Searchable, SoftDeletes, Sortable;

    protected $fillable = [
        'name',
        'type',
        'phone',
        'street_address',
        'roi',
    ];

    protected function searchableFields(): array
    {
        return [
            'name',
            'phone',
            'street_address',
        ];
    }

    protected function sortableFields(): array
    {
        return [
            'name' => 'name',
            'type' => 'type',
        ];
    }

    public function contactable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Build the paginated contact listing and the available contact types.
     *
     * @return array{contacts: LengthAwarePaginator, types: list<string>}
     */
    public function scopeListing(Builder $query): array
    {
        return [
            'contacts' => $query->orderByDesc('id')->paginate(15),
            'types' => array_column(ContactType::cases(), 'value'),
        ];
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    protected function casts(): array
    {
        return [
            'type' => ContactType::class,
            'roi' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnlyDirty()->logFillable();
    }
}
