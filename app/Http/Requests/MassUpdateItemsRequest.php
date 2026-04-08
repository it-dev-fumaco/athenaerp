<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class MassUpdateItemsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'brand' => ['nullable', 'string', 'max:140'],
            'item_classification' => ['nullable', 'string', 'max:140'],
            'last_movement_days' => ['nullable', 'integer', 'in:30,60,90,120,150,300,365'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        // no-op (kept for future validator hooks)
    }
}
