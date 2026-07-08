<?php

namespace App\Http\Controllers;

use App\Actions\CreateContactAction;
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

    public function store(StoreContactRequest $request, CreateContactAction $createContact): RedirectResponse
    {
        $this->authorize('create', Contact::class);

        $createContact->execute($request->validated());

        return redirect()->back()->with('success', __('flash.contacts.created'));
    }

    public function update(UpdateContactRequest $request, Contact $contact): RedirectResponse
    {
        $this->authorize('update', $contact);

        $contact->update($request->validated());

        return redirect()->back()->with('success', __('flash.contacts.updated'));
    }

    public function destroy(Contact $contact): RedirectResponse
    {
        $this->authorize('delete', $contact);

        $contact->delete();

        return redirect()->back()->with('success', __('flash.contacts.deleted'));
    }
}
