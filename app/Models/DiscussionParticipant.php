<?php

namespace App\Models;

use App\Contracts\LinksActivityToPatient;
use Database\Factories\DiscussionParticipantFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class DiscussionParticipant extends Model implements LinksActivityToPatient
{
    /** @use HasFactory<DiscussionParticipantFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'discussion_id',
        'participantable_id',
        'participantable_type',
        'seen_at',
        'is_initiator',
    ];

    public function discussion(): BelongsTo
    {
        return $this->belongsTo(Discussion::class);
    }

    public function participantable(): MorphTo
    {
        return $this->morphTo();
    }

    /** @param Builder<DiscussionParticipant> $query */
    public function scopeInitiator(Builder $query): void
    {
        $query->where('is_initiator', true);
    }

    protected function casts(): array
    {
        return [
            'seen_at' => 'datetime',
            'is_initiator' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnlyDirty()->logFillable();
    }

    public function auditPatientId(): ?int
    {
        return Discussion::withTrashed()->find($this->discussion_id)?->auditPatientId();
    }
}
