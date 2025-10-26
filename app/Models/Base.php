<?php

/** @noinspection PhpUnused */
/** @noinspection LaravelEloquentGuardedAttributeAssignmentInspection */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

// #[ObservedBy([BaseObserver::class])]
class Base extends Model implements HasMedia
{
    use InteractsWithMedia, SoftDeletes;

    public function getFillable(): array
    {
        return array_merge(parent::getFillable(), [
            'created_by_id',
            'updated_by_id',
            'deleted_by_id',
            'created_at',
            'updated_at',
            'deleted_at',
        ]);
    }

    public function deleteWithUser(): bool
    {
        return $this->update([
            'deleted_at' => now(),
            'deleted_by' => auth()->id() ?? 1,
        ]);
    }

    public function created_by(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function updated_by(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }

    public function deleted_by(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by_id');
    }

    /**
     * Configure the activity log options for the model.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logExcept([
                'updated_at',
                'created_at',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('Database');
    }
}
