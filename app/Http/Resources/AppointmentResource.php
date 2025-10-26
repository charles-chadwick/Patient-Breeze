<?php
/** @noinspection PhpUndefinedFieldInspection */

namespace App\Http\Resources;

use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Appointment */
class AppointmentResource extends JsonResource
{
    public function toArray(Request $request) : array
    {
        if (empty($this->id)) {
            return parent::toArray($request);
        }

        return [
            'type'          => 'patient',
            'id'            => $this->id,
            'attributes'    => [
                'id'          => $this->id,
                'patient_id'  => $this->patient_id,
                'start'       => $this->start->format('m/d/Y h:i A'),
                'end'         => $this->end->format('m/d/Y h:i A'),
                'status'      => $this->status,
                'type'        => $this->type,
                'title'       => $this->title,
                'description' => $this->description,
                'created_at'  => $this->created_at->format('m/d/Y h:i A'),
            ],
            'relationships' => [
                'patient' => new PatientResource($this->whenLoaded('patient')),
                'created_by' => new UserResource($this->whenLoaded('created_by')),
            ]
        ];
    }
}
