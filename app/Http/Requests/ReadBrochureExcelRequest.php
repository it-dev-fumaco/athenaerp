<?php

namespace App\Http\Requests;

class ReadBrochureExcelRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'selected-file' => ['required', 'file', 'mimes:xlsx,xls'],
            'is_readonly' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'selected-file.required' => 'No file uploaded.',
        ];
    }
}
