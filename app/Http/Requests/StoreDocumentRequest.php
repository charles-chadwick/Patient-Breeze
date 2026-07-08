<?php

namespace App\Http\Requests;

use App\Enums\DocumentType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(DocumentType::values())],
            'name' => ['nullable', 'string', 'max:255'],
            'document_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx,txt', 'max:10240'],
        ];
    }
}
