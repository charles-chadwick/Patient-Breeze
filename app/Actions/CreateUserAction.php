<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CreateUserAction
{
    public function __construct(private ManageAvatarAction $avatarAction) {}

    /**
     * @param  array<string, mixed>  $validated
     */
    public function execute(array $validated, ?UploadedFile $avatar = null): User
    {
        return DB::transaction(function () use ($validated, $avatar) {
            $user = User::create(array_merge(User::identityData($validated), [
                'password' => Hash::make($validated['password']),
            ]));

            $user->syncRoles([$validated['role']]);
            $this->avatarAction->execute($user, $avatar, false);

            return $user;
        });
    }
}
