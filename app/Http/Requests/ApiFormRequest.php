<?php

namespace App\Http\Requests;

use App\Http\Helpers\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Base Form Request that returns legacy API response shape on validation failure
 * ({ "status": 0, "message": "..." }) so frontends expecting ApiResponse::failure() keep working.
 */
abstract class ApiFormRequest extends FormRequest
{
    /**
     * Handle a failed validation attempt: return ApiResponse::failure() with first error message
     * so response format matches the original controller behavior.
     */
    protected function failedValidation(Validator $validator): void
    {
        $message = $validator->errors()->first();

        throw new HttpResponseException(
            ApiResponse::failure($message)
        );
    }
}
