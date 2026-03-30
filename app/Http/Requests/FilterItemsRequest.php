<?php

namespace App\Http\Requests;

use App\Models\Item;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FilterItemsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => [
                'required',
                'array',
                'min:1'
            ],
            'status.*' => [
                'string',
                Rule::in(Item::LIFECYCLE_STATUSES)
            ],
            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:100'
            ]
        ];
    }
}