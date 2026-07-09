<?php

namespace App\Models;

use App\Enums\NoteType;
use App\Models\Concerns\Searchable;
use App\Models\Concerns\Sortable;
use Database\Factories\NoteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Note extends Model
{
    /** @use HasFactory<NoteFactory> */
    use HasFactory, LogsActivity, Searchable, SoftDeletes, Sortable;

    protected $fillable = [
        'type',
        'title',
        'content',
    ];

    protected function searchableFields(): array
    {
        return [
            'title',
            'content',
        ];
    }

    protected function sortableFields(): array
    {
        return [
            'title' => 'title',
            'type' => 'type',
        ];
    }

    public function notable(): MorphTo
    {
        return $this->morphTo();
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    protected function casts(): array
    {
        return [
            'type' => NoteType::class,
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnlyDirty()->logFillable();
    }
}
