<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AppointmentRequest extends FormRequest
{
    public function rules() : array
    {
        return [
            'patient_id'  => [
                'required',
                'exists:patients'
            ],
            'start'       => [
                'required',
                'date'
            ],
            'end'         => [
                'required',
                'date'
            ],
            'status'      => ['required'],
            'type'        => ['required'],
            'title'       => ['required'],
            'description' => ['required'],
            
        ];
    }

    public function authorize() : bool
    {
        return true;
    }
}
