<?php

namespace App\Http\Requests;

class AddToBrochureListRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'item_codes' => ['nullable', 'array'],
            'item_codes.*' => ['string'],
            'fitting_type' => ['nullable', 'array'],
            'location' => ['nullable', 'array'],
            'description' => ['nullable', 'array'],
            'item_name' => ['nullable', 'array'],
            'project' => ['nullable', 'string'],
            'customer' => ['nullable', 'string'],
            'save' => ['nullable', 'boolean'],
            'id_arr' => ['nullable', 'array'],
            'id_arr.*' => ['string'],
            'generate_page' => ['nullable'],
        ];
    }
}
