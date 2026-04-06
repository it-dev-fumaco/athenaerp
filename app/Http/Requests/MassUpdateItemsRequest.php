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
            'last_movement_days_min' => ['nullable', 'integer', 'min:0', 'max:36500'],
            'last_movement_days_max' => ['nullable', 'integer', 'min:0', 'max:36500'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $data = $v->getData();
            $min = $data['last_movement_days_min'] ?? null;
            $max = $data['last_movement_days_max'] ?? null;
            if ($min !== null && $max !== null && (int) $min > (int) $max) {
                $v->errors()->add(
                    'last_movement_days_max',
                    'Last movement maximum days must be greater than or equal to minimum days.'
                );
            }
        });
    }
}
