<?php
/** @noinspection PhpUndefinedFieldInspection */

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request) : array
    {
        if (!isset($this->id)) {
            return parent::toArray($request);
        }

        return [
            'type'          => 'user',
            'id'            => $this->id,
            'attributes'    => [
                'id'         => $this->id,
                'role'       => $this->role,
                'prefix'     => $this->prefix,
                'first_name' => $this->first_name,
                'last_name'  => $this->last_name,
                'suffix'     => $this->suffix,
                'email'      => $this->email,
                'created_at' => $this->created_at?->format('m/d/Y h:i A'),
                'avatar'     => $this?->avatar,
                'full_name'  => $this->full_name
            ],
            'relationships' => [
                'created_by' => new UserResource($this->whenLoaded('created_by')),
            ]
        ];
    }
}
