<?php

namespace App\Models;

use App\Models\Concerns\Searchable;
use App\Models\Concerns\Sortable;
use Database\Factories\DiscussionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnlyDirty()->logFillable();
    }
}
