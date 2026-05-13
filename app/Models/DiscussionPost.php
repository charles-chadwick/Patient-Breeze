<?php

namespace App\Models;

use App\Enums\DiscussionPostStatus;
use Database\Factories\DiscussionPostFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class DiscussionPost extends Model
{
    /** @use HasFactory<DiscussionPostFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'discussion_id',
        'status',
        'content',
    ];

    public function discussion(): BelongsTo
    {
        return $this->belongsTo(Discussion::class);
    }

    protected function casts(): array
    {
        return [
            'status' => DiscussionPostStatus::class,
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnlyDirty()->logFillable();
    }
}
