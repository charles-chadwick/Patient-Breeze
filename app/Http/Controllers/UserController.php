<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\UserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Hash;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

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
        return Inertia::render('Users/Create', ['user_roles' => UserRole::toArray()]);
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
