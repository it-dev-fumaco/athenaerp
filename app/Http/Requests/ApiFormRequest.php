<?php

namespace App\Http\Requests;

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
     * Handle a failed validation attempt: return 422 with message and errors
     * so frontends can show the first error and debug which field failed.
     */
    protected function failedValidation(Validator $validator): void
    {
        $message = $validator->errors()->first();

        throw new HttpResponseException(
            response()->json([
                'status' => 0,
                'message' => $message,
                'errors' => $validator->errors()->toArray(),
            ], 422)
        );
    }
}
