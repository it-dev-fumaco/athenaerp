<?php

namespace App\Http\Requests;

class UpdateBrochureAttributesRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'item_code' => ['required', 'string'],
            'attribute' => ['nullable', 'array'],
            'attribute.*' => ['string'],
            'current_attribute' => ['required', 'array'],
            'current_attribute.*' => ['string'],
            'hidden_attributes' => ['nullable', 'array'],
            'hidden_attributes.*' => ['string'],
            'remarks' => ['nullable', 'string'],
        ];
    }
}
