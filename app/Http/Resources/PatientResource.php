<?php

namespace App\Http\Resources;

use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Patient */
class PatientResource extends JsonResource
{
    public function toArray(Request $request) : array
    {
        return [
            'id'              => $this->id,
            'status'          => $this->status,
            'prefix'          => $this->prefix,
            'first_name'      => $this->first_name,
            'middle_name'     => $this->middle_name,
            'last_name'       => $this->last_name,
            'suffix'          => $this->suffix,
            'dob'             => $this->dob,
            'gender'          => $this->gender,
            'gender_identity' => $this->gender_identity,
            'email'           => $this->email,
            'password'        => $this->password,
            'created_at'      => $this->created_at,
            'updated_at'      => $this->updated_at,
            
        ];
    }
}
