<?php

namespace App\Http\Controllers;

use App\Actions\CreateUserAction;
use App\Actions\UpdateUserAction;
use App\Enums\ContactType;
use App\Enums\UserRole;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

    /**
     * Search users for participant/assignee pickers, excluding the current user.
     */
    public function search(Request $request): JsonResponse
    {
        $search = $request->string('search')->trim()->toString();

        $users = User::whereKeyNot(Auth::id())->forPicker($search);

        return response()->json(['users' => $users]);
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

        return redirect()->route('users.index')
            ->with('success', __('flash.users.created'));
    }

    public function show(User $user, Request $request): Response
    {
        $this->authorize('view', $user);

        $search = $request->string('search')->trim()->toString();

        $user->load(['media', 'roles', 'contacts' => fn ($query) => $query->orderBy('name')]);

        return Inertia::render('Users/Show', [
            'user' => $user,
            'appointments' => $user->paginatedAppointments($search),
            'appointment_search' => $search,
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

        return redirect()->route('users.index')
            ->with('success', __('flash.users.updated'));
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        $user->delete();

        return redirect()->route('users.index')
            ->with('success', __('flash.users.deleted'));
    }
}
