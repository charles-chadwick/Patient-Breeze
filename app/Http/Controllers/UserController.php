<?php

namespace App\Http\Controllers;

use App\Actions\CreateUserAction;
use App\Actions\UpdateUserAction;
use App\Enums\ContactType;
use App\Enums\UserRole;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        return Inertia::render('Users/Index', [
            ...User::listing($request),
            'role_options' => array_column(UserRole::cases(), 'value'),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', User::class);

        return Inertia::render('Users/Form', [
            'role_options' => array_column(UserRole::cases(), 'value'),
        ]);
    }

    public function store(StoreUserRequest $request, CreateUserAction $createUser): RedirectResponse
    {
        $this->authorize('create', User::class);

        $createUser->execute($request->validated(), $request->file('avatar'));

        return redirect()->route('users.index');
    }

    public function show(User $user, Request $request): Response
    {
        $this->authorize('view', $user);

        $search = $request->string('search')->trim();

        $user->load(['media', 'roles', 'contacts' => fn ($query) => $query->orderBy('name')]);

        $appointments = $user->appointments()
            ->with(['patient.media'])
            ->when($search, fn ($query) => $query->matchingReasonOrPatientName($search))
            ->orderBy('date', 'desc')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Users/Show', [
            'user' => $user,
            'appointments' => $appointments,
            'appointment_search' => $search->toString(),
            'contact_types' => ContactType::values(),
            'contactable_type' => User::class,
        ]);
    }

    public function edit(User $user): Response
    {
        $this->authorize('update', $user);

        $user->load(['media', 'roles']);

        return Inertia::render('Users/Form', [
            'user' => $user,
            'role_options' => array_column(UserRole::cases(), 'value'),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user, UpdateUserAction $updateUser): RedirectResponse
    {
        $this->authorize('update', $user);

        $updateUser->execute($user, $request->validated(), $request->file('avatar'));

        return redirect()->route('users.index');
    }
}
