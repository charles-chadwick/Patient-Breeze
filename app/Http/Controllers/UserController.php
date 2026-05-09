<?php

namespace App\Http\Controllers;

use App\Actions\ManageAvatarAction;
use App\Enums\ContactType;
use App\Enums\UserRole;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim();
        $sort_by = $request->string('sort_by', 'last_name')->toString();
        $direction = $request->input('direction') === 'desc' ? 'desc' : 'asc';

        $users = User::with(['media', 'roles'])
            ->staff()
            ->when($search, fn ($query) => $query->search($search))
            ->sort($sort_by, $direction)
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Users/Index', [
            'users' => $users,
            'search' => $search->toString(),
            'sort_by' => $sort_by,
            'direction' => $direction,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Users/Form', [
            'role_options' => array_column(UserRole::cases(), 'value'),
        ]);
    }

    public function store(StoreUserRequest $request, ManageAvatarAction $avatarAction): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($request, $validated, $avatarAction) {
            $user = User::create(array_merge(User::identityData($validated), [
                'password' => Hash::make($validated['password']),
            ]));

            $user->syncRoles([$validated['role']]);
            $avatarAction->execute($user, $request->file('avatar'), false);
        });

        return redirect()->route('users.index');
    }

    public function show(User $user, Request $request): Response
    {
        $search = $request->string('search')->trim();

        $user->load(['media', 'roles', 'contacts' => fn ($q) => $q->orderBy('name')]);

        $appointments = $user->appointments()
            ->with(['patient.media'])
            ->when($search, fn ($query) => $query->where(fn ($q) => $q
                ->where('reason', 'like', "%{$search}%")
                ->orWhereHas('patient', fn ($pq) => $pq
                    ->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                )
            ))
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

    public function update(UpdateUserRequest $request, User $user, ManageAvatarAction $avatarAction): RedirectResponse
    {
        $validated = $request->validated();

        $user->update(User::identityData($validated));

        if (filled($validated['password'] ?? null)) {
            $user->update(['password' => Hash::make($validated['password'])]);
        }

        $user->syncRoles([$validated['role']]);
        $avatarAction->execute($user, $request->file('avatar'), (bool) ($validated['remove_avatar'] ?? false));

        return redirect()->route('users.index');
    }
}
