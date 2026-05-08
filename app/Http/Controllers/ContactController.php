<?php

namespace App\Http\Controllers;

use App\Enums\ContactType;
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
        $contacts = Contact::orderByDesc('id')->paginate(15);

        return Inertia::render('Contacts/Index', [
            'contacts' => $contacts,
            'types' => array_column(ContactType::cases(), 'value'),
        ]);
    }

    public function store(StoreContactRequest $request): RedirectResponse
    {
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
        $contact->update($request->validated());

        return redirect()->back();
    }

    public function destroy(Contact $contact): RedirectResponse
    {
        $contact->delete();

        return redirect()->back();
    }
}
