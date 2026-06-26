<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;

class UpdateUserAction
{
    public function __construct(private ManageAvatarAction $avatarAction) {}

    /**
     * @param  array<string, mixed>  $validated
     */
    public function execute(User $user, array $validated, ?UploadedFile $avatar = null): User
    {
        $user->update(User::identityData($validated));

        if (filled($validated['password'] ?? null)) {
            $user->update(['password' => Hash::make($validated['password'])]);
        }

        $user->syncRoles([$validated['role']]);
        $this->avatarAction->execute($user, $avatar, (bool) ($validated['remove_avatar'] ?? false));

        return $user;
    }
}
