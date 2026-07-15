<?php

namespace App\Models;

use App\Contracts\LinksActivityToPatient;
use App\Support\ResolvesActivityPatient;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\MediaLibrary\MediaCollections\Models\Media as BaseMedia;

class Media extends BaseMedia implements LinksActivityToPatient
{
    use LogsActivity, Prunable, SoftDeletes;

    /**
     * How long a soft-deleted file is retained before its row and underlying file are
     * removed for good. Soft-deleted media keep their files on disk, because the media
     * library observer only removes files on a force delete, so without pruning every
     * replaced avatar or removed document would orphan its file forever.
     */
    public const PRUNE_TRASHED_AFTER_DAYS = 30;

    /**
     * Media library's base model sets $guarded = [], so getFillable() is empty and the
     * logFillable() used elsewhere would record nothing. Attributes are listed explicitly
     * instead, omitting custom_properties, manipulations, generated_conversions and
     * responsive_images, which are library-managed churn rather than user actions.
     *
     * Writing those omitted columns still fires an update, so empty logs are suppressed to
     * keep a bare "File updated" entry off the timeline every time a conversion finishes.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnlyDirty()->dontLogEmptyChanges()->logOnly([
            'collection_name', 'name', 'file_name', 'mime_type', 'size', 'disk',
        ]);
    }

    /**
     * Media has no patient of its own; it inherits one from the model it is attached to,
     * which is what the shared resolver already computes for any owner implementing
     * the contract (Patient, Document, Discussion, Contact) and null for the rest.
     */
    public function auditPatientId(): ?int
    {
        return app(ResolvesActivityPatient::class)->resolve($this->model_type, $this->model_id);
    }

    /**
     * Prunable rather than MassPrunable on purpose: pruning must fire model events so the
     * media library observer sees a force delete and removes the file from disk.
     *
     * @return Builder<Media>
     */
    public function prunable(): Builder
    {
        return static::onlyTrashed()
            ->where('deleted_at', '<=', now()->subDays(self::PRUNE_TRASHED_AFTER_DAYS));
    }
}
