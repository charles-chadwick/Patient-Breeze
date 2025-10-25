<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
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

    public function store(StoreUserRequest $request)
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

        return Inertia::render('Users/Show', ['user' => new UserResource($user)]);
    }

    public function edit(User $user) {}

    public function update(Request $request, User $user) {}

    public function destroy(User $user) {}

    /**
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
     */
    public function uploadAvatar(Request $request)
    {
        $request->validate([
            'avatar' => [
                'required',
                'image',
                'max:2048',
            ],
        ]);

        $user = User::findOrFail($request->route('user'));
        $user->addMediaFromRequest('avatar')
            ->toMediaCollection('avatars');

        return back()->with('message', 'Avatar uploaded successfully');
    }

    public function removeAvatar(User $user)
    {
        Media::where('model_type', User::class)
            ->where('model_id', $user->id)
            ->delete();
    }
}