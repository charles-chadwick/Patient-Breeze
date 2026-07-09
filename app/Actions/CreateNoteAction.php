<?php

namespace App\Actions;

use App\Models\Note;
use Illuminate\Database\Eloquent\Model;

class CreateNoteAction
{
    /**
     * @param  array<string, mixed>  $validated
     */
    public function execute(array $validated): Note
    {
        /** @var Model $parent */
        $parent = $validated['notable_type']::query()->findOrFail($validated['notable_id']);

        /** @var Note $note */
        $note = $parent->notes()->create([
            'type' => $validated['type'],
            'title' => $validated['title'],
            'content' => $validated['content'],
        ]);

        return $note;
    }
}
