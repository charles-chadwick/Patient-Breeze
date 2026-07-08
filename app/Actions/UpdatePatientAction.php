<?php

namespace App\Actions;

use App\Models\Patient;
use Illuminate\Http\UploadedFile;

class UpdatePatientAction
{
    public function __construct(private ManageAvatarAction $avatarAction) {}

    /**
     * @param  array<string, mixed>  $validated
     */
    public function execute(Patient $patient, array $validated, ?UploadedFile $avatar = null): Patient
    {
        $patient->update([
            'prefix' => $validated['prefix'] ?? '',
            'first_name' => $validated['first_name'],
            'middle_name' => $validated['middle_name'] ?? '',
            'last_name' => $validated['last_name'],
            'suffix' => $validated['suffix'] ?? '',
            'email' => $validated['email'],
            'date_of_birth' => $validated['date_of_birth'],
            'gender_at_birth' => $validated['gender_at_birth'],
            'gender_identity' => $validated['gender_identity'] ?? null,
            'blood_type' => $validated['blood_type'] ?? null,
        ]);

        $this->avatarAction->execute($patient, $avatar, (bool) ($validated['remove_avatar'] ?? false));

        return $patient;
    }
}
