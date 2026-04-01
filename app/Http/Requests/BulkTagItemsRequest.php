<?php

namespace App\Http\Requests;

use App\Models\Item;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
                'min:1',
            ],
            'itemIds.*' => [
                'required',
                'string',
                Rule::exists('tabItem', 'name'),
            ],
            'tag' => [
                'required',
                'string',
                Rule::in(Item::LIFECYCLE_STATUSES),
            ],
        ];
    }
}
