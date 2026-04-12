<?php

namespace App\Http\Resources;

use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Patient
 */
class PatientResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'date_of_birth' => $this->date_of_birth?->toDateString(),
            'gender_at_birth' => $this->gender_at_birth?->value,
            'gender_identity' => $this->gender_identity?->value,
            'blood_type' => $this->blood_type,
            'user' => UserResource::make($this->whenLoaded('user')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
