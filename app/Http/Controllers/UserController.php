<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\UserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Hash;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('created_by')
            ->orderBy(request('sort_by', 'id'), request('sort_direction', 'asc'))
            ->searchAny(request('search'))
            ->paginate()
            ->withQueryString();

        return Inertia::render('Users/Index', ['users' => UserResource::collection($users)]);
    }

    public function create()
    {
        $user_roles = collect(UserRole::cases())
            ->map(function ($role) {
                return [
                    'value' => $role->value,
                    'name' => $role->name,
                ];
            })
            ->toArray();

        return Inertia::render('Users/Create', ['user_roles' => $user_roles]);
    }

    public function store(UserRequest $request)
    {
        $data = $request->validated();
        $data['role'] = $request->role['value'];
        $data['password'] = Hash::make($request->password);
        $user = User::create($data);

        return to_route('users.index')->with('message', "{$user->first_name} {$user->last_name} created successfully");
    }

    public function profile(User $user)
    {
        $user->load('created_by');

        return Inertia::render('Users/Profile', ['user' => new UserResource($user)]);
    }

    public function edit(User $user) {}

    public function update(UserRequest $request, User $user) {}

    public function destroy(User $user) {}

}
