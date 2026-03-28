<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UploadItemImageRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $allowed = implode(',', ['jpg', 'jpeg', 'png', 'webp']);

        return [
            'item_code' => ['required', 'string', 'max:255'],

            // Keeps existing image rows (tabItem Images.name) so the controller can delete only removed images.
            'existing_images' => ['nullable', 'array'],
            'existing_images.*' => ['nullable', 'string', 'max:255'],

            // When present, controller will (re)generate webp + thumbnail and insert new tabItem Images rows.
            'item_image' => ['nullable', 'array'],
            'item_image.*' => ['file', 'mimes:'.$allowed, 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'item_code.required' => 'Item code is required.',
            'item_image.*.mimes' => 'Sorry, only .jpeg, .jpg, .png and .webp files are allowed.',
            'item_image.*.max' => 'The image may not be greater than 10 MB.',
        ];
    }
}

