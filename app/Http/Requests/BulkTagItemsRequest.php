<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Item;

class BulkTagItemsRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'itemIds' => [
                'required',
                'array',
                'min:1'
            ],
            'itemIds.*' => [
                'required',
                Rule::exists(Item::class, 'id')
            ],
            'tag' => [
                'required',
                'string',
                Rule::in(Item::LIFECYCLE_STATUSES)
            ],
        ];
    }
}