<?php

namespace App\Actions;

use App\Models\Patient;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class CreatePatientAction
{
    public function __construct(private ManageAvatarAction $avatarAction) {}

    /**
     * @param  array<string, mixed>  $validated
     */
    public function execute(array $validated, ?UploadedFile $avatar = null): Patient
    {
        return DB::transaction(function () use ($validated, $avatar) {
            $patient = Patient::create([
                'prefix' => $validated['prefix'] ?? '',
                'first_name' => $validated['first_name'],
                'middle_name' => $validated['middle_name'] ?? '',
                'last_name' => $validated['last_name'],
                'suffix' => $validated['suffix'] ?? '',
                'email' => $validated['email'],
                'mrn' => Patient::generateMrn(),
                'date_of_birth' => $validated['date_of_birth'],
                'gender_at_birth' => $validated['gender_at_birth'],
                'gender_identity' => $validated['gender_identity'] ?? null,
                'blood_type' => $validated['blood_type'] ?? null,
            ]);

            $this->avatarAction->execute($patient, $avatar, false);

            return $patient;
        });
    }
}
