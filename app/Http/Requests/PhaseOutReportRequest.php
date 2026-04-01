<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PhaseOutReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tagged_per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'candidates_per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'tagged_page' => ['nullable', 'integer', 'min:1'],
            'candidates_page' => ['nullable', 'integer', 'min:1'],
            'months' => ['nullable', 'integer', 'min:1', 'max:120'],
            'brand' => ['nullable', 'string', 'max:140'],
            'created_before' => ['nullable', 'date'],
            'no_movement_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'excess_stock_only' => ['nullable', 'boolean'],
        ];
    }
}
