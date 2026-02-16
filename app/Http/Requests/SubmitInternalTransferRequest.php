<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitInternalTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'child_tbl_id' => ['required', 'string'],
            'barcode' => ['required', 'string'],
            'qty' => ['required', 'numeric', 'min:0.01'],
        ];
    }
}
