<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactRequest;
use App\Http\Requests\UpdateContactRequest;
use App\Models\Contact;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ContactController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Contact::class);

        return Inertia::render('Contacts/Index', Contact::listing());
    }

    public function store(StoreContactRequest $request): RedirectResponse
    {
        $this->authorize('create', Contact::class);

        $validated = $request->validated();

        $parent = $validated['contactable_type']::query()->findOrFail($validated['contactable_id']);

        $parent->contacts()->create([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'phone' => $validated['phone'] ?? null,
            'street_address' => $validated['street_address'] ?? null,
            'roi' => $validated['roi'] ?? null,
        ]);

        return redirect()->back();
    }

    public function update(UpdateContactRequest $request, Contact $contact): RedirectResponse
    {
        $this->authorize('update', $contact);

        $contact->update($request->validated());

        return redirect()->back();
    }

    public function destroy(Contact $contact): RedirectResponse
    {
        $this->authorize('delete', $contact);

        $contact->delete();

        return redirect()->back();
    }
}
