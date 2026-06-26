<?php

namespace App\Http\Controllers;

use App\Actions\CreateUserAction;
use App\Actions\UpdateUserAction;
use App\Enums\ContactType;
use App\Enums\UserRole;
use App\Http\Controllers\Concerns\WithSearch;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    use WithSearch;

    public function index(Request $request): Response
    {
        ['search' => $search, 'sort_by' => $sort_by, 'direction' => $direction] = $this->searchParameters($request);

        $users = User::with(['media', 'roles'])
            ->staff()
            ->when($search, fn ($query) => $query->search($search))
            ->sort($sort_by, $direction)
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Users/Index', [
            'users' => $users,
            'search' => $search,
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

    public function store(StoreUserRequest $request, CreateUserAction $createUser): RedirectResponse
    {
        $createUser->execute($request->validated(), $request->file('avatar'));

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

    public function update(UpdateUserRequest $request, User $user, UpdateUserAction $updateUser): RedirectResponse
    {
        $updateUser->execute($user, $request->validated(), $request->file('avatar'));

        return redirect()->route('users.index');
    }
}
