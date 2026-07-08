<?php

namespace App\Actions;

use App\Models\Document;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;

class AttachDocumentAction
{
    /**
     * Create a document on the given chart and attach the uploaded file to it.
     *
     * @param  Model&HasMedia  $documentable  The chart owner (e.g. Patient) the document belongs to.
     * @param  array{type: string, name: ?string, document_date: ?string, notes: ?string}  $attributes
     * @param  Model  $uploader  The authenticated user or patient performing the upload.
     *
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
     */
    public function execute(Model&HasMedia $documentable, array $attributes, UploadedFile $file, Model $uploader): Document
    {
        /** @var Document $document */
        $document = $documentable->documents()->make([
            'type' => $attributes['type'],
            'name' => ($attributes['name'] ?? null) ?: $file->getClientOriginalName(),
            'document_date' => $attributes['document_date'] ?? null,
            'notes' => $attributes['notes'] ?? null,
        ]);

        $document->uploader()->associate($uploader);
        $document->save();

        $document->addMedia($file)->toMediaCollection('file');

        return $document;
    }
}
