<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PatientRequest extends FormRequest
{
    public function rules() : array
    {
        return [
            'status'          => ['required'],
            'prefix'          => ['nullable'],
            'first_name'      => ['required'],
            'middle_name'     => ['nullable'],
            'last_name'       => ['required'],
            'suffix'          => ['nullable'],
            'dob'             => [
                'required',
                'date'
            ],
            'gender'          => ['required'],
            'gender_identity' => ['nullable'],
            'email'           => [
                'nullable',
                'email',
                'max:254'
            ],
            'password'        => ['nullable'],
            
        ];
    }

    public function authorize() : bool
    {
        return true;
    }
}
