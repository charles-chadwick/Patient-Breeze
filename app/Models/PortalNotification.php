<?php

namespace App\Models;

use Database\Factories\PortalNotificationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Collection;

class PortalNotification extends Model
{
    /** @use HasFactory<PortalNotificationFactory> */
    use HasFactory;

    protected $fillable = [
        'type',
        'notifiable_id',
        'notifiable_type',
        'patient_id',
        'title',
        'body',
        'url',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    /**
     * Build the most recent notification queue and the unread count.
     *
     * @return array{notifications: Collection<int, array<string, mixed>>, unread_count: int}
     */
    public function scopeQueue(Builder $query): array
    {
        return [
            'notifications' => $query->with('patient:id,first_name,last_name,mrn')
                ->latest()
                ->limit(100)
                ->get()
                ->map(fn (PortalNotification $notification) => [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'title' => $notification->title,
                    'body' => $notification->body,
                    'url' => $notification->url,
                    'read_at' => $notification->read_at,
                    'created_at' => $notification->created_at,
                    'patient' => $notification->patient
                        ? $notification->patient->only(['id', 'first_name', 'last_name', 'mrn'])
                        : null,
                ]),
            'unread_count' => static::unread()->count(),
        ];
    }

    public function markAsRead(): void
    {
        if ($this->read_at === null) {
            $this->forceFill(['read_at' => now()])->save();
        }
    }
}
