<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStockEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ste_no' => ['required', 'string'],
            'qty' => ['required', 'numeric', 'min:0.01'],
        ];
    }
}
