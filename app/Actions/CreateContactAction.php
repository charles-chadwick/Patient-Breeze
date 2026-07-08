<?php

namespace App\Actions;

use App\Models\Contact;
use Illuminate\Database\Eloquent\Model;

class CreateContactAction
{
    /**
     * @param  array<string, mixed>  $validated
     */
    public function execute(array $validated): Contact
    {
        /** @var Model $parent */
        $parent = $validated['contactable_type']::query()->findOrFail($validated['contactable_id']);

        /** @var Contact $contact */
        $contact = $parent->contacts()->create([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'phone' => $validated['phone'] ?? null,
            'street_address' => $validated['street_address'] ?? null,
            'roi' => $validated['roi'] ?? null,
        ]);

        return $contact;
    }
}
