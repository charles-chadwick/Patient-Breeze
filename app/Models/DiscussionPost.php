<?php

namespace App\Models;

use App\Contracts\LinksActivityToPatient;
use App\Enums\DiscussionPostStatus;
use Database\Factories\DiscussionPostFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class DiscussionPost extends Model implements LinksActivityToPatient
{
    /** @use HasFactory<DiscussionPostFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'discussion_id',
        'user_id',
        'patient_id',
        'status',
        'content',
    ];

    public function discussion(): BelongsTo
    {
        return $this->belongsTo(Discussion::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function isFromPatient(): bool
    {
        return $this->patient_id !== null;
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

    public function auditPatientId(): ?int
    {
        if ($this->patient_id !== null) {
            return $this->patient_id;
        }

        return Discussion::withTrashed()->find($this->discussion_id)?->auditPatientId();
    }
}
