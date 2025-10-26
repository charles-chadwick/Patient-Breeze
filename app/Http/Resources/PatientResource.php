<?php

namespace App\Http\Resources;

use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Patient */
class PatientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if (empty($this->id)) {
            return parent::toArray($request);
        }

        return [
            'type' => '',
            'id' => $this->id,
            'attributes' => [
                'id' => $this->id,
                'status' => $this->status,
                'prefix' => $this->prefix,
                'first_name' => $this->first_name,
                'middle_name' => $this->middle_name,
                'last_name' => $this->last_name,
                'suffix' => $this->suffix,
                'dob' => $this?->dob->format('m/d/Y'),
                'gender' => $this->gender,
                'gender_identity' => $this->gender_identity,
                'email' => $this->email,
                'created_at' => $this->created_at->format('m/d/Y h:i A'),
                'full_name' => $this->full_name,
                'avatar' => $this->avatar,
            ],
            'relationships' => [
                'appointments' => new AppointmentResource($this->whenLoaded('appointments')),
                'created_by' => new UserResource($this->whenLoaded('created_by')),
            ],
            'links' => [
                'self' => '',
            ],
        ];

    }
}
