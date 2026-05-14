<?php

namespace App\Models;

use App\Enums\DiscussionType;
use App\Models\Concerns\Searchable;
use App\Models\Concerns\Sortable;
use Database\Factories\DiscussionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Discussion extends Model implements HasMedia
{
    /** @use HasFactory<DiscussionFactory> */
    use HasFactory, InteractsWithMedia, LogsActivity, Searchable, SoftDeletes, Sortable;

    protected $fillable = [
        'discussionable_id',
        'discussionable_type',
        'type',
        'title',
        'status',
    ];

    protected function searchableFields(): array
    {
        return [
            'title',
            'type',
            'status',
        ];
    }

    protected function sortableFields(): array
    {
        return [
            'title' => 'title',
            'type' => 'type',
            'status' => 'status',
        ];
    }

    public function discussionable(): MorphTo
    {
        return $this->morphTo();
    }

    public function participants(): HasMany
    {
        return $this->hasMany(DiscussionParticipant::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(DiscussionPost::class);
    }

    protected function casts(): array
    {
        return [
            'type' => DiscussionType::class,
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnlyDirty()->logFillable();
    }
}
