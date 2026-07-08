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

        $users = User::query()
            ->whereKeyNot(Auth::id())
            ->when($search !== '', fn ($query) => $query->withSearch($search))
            ->with(['media' => fn ($query) => $query->where('collection_name', 'avatar')])
            ->orderBy('last_name')
            ->limit(20)
            ->get(['id', 'first_name', 'last_name'])
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'avatar_url' => $user->avatar_url,
            ]);

        return response()->json(['users' => $users]);
    }

    public function create(): Response
    {
        return Inertia::render('Users/Form', [
            'role_options' => array_column(UserRole::cases(), 'value'),
        ]);
    }

    public function store(StoreUserRequest $request, CreateUserAction $createUser): RedirectResponse
    {
        $createUser->execute($request->validated(), $request->file('avatar'));

        return redirect()->route('users.index')
            ->with('success', __('flash.users.created'));
    }

    public function show(User $user, Request $request): Response
    {
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
        $user->load(['media', 'roles']);

        return Inertia::render('Users/Form', [
            'user' => $user,
            'role_options' => array_column(UserRole::cases(), 'value'),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user, UpdateUserAction $updateUser): RedirectResponse
    {
        $updateUser->execute($user, $request->validated(), $request->file('avatar'));

        return redirect()->route('users.index')
            ->with('success', __('flash.users.updated'));
    }
}
