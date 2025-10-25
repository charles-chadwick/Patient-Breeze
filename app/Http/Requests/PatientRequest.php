<?php

namespace App\Http\Requests;

use App\Enums\Gender;
use App\Enums\PatientStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PatientRequest extends FormRequest
{
    public function rules() : array
    {
        return [
            'status'          => [
                'required',
                Rule::in(PatientStatus::cases())
            ],
            'prefix'          => ['nullable'],
            'first_name'      => ['required'],
            'middle_name'     => ['nullable'],
            'last_name'       => ['required'],
            'suffix'          => ['nullable'],
            'dob'             => [
                'required',
                'date'
            ],
            'gender'          => [
                'required',
                Rule::in(Gender::cases())
            ],
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
