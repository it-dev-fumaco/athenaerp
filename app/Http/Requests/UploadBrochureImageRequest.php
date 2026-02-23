<?php

namespace App\Http\Requests;

class UploadBrochureImageRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $allowed = implode(',', ['jpg', 'jpeg', 'png', 'webp']);

        return [
            'selected-file' => ['required', 'file', 'mimes:'.$allowed],
            'project' => ['required', 'string'],
            'filename' => ['required', 'string'],
            'row' => ['required', 'integer'],
            'column' => ['required', 'string'],
            'item_image_id' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'selected-file.required' => 'No file selected.',
            'selected-file.mimes' => 'Sorry, only .jpeg, .jpg, .png and .webp files are allowed.',
        ];
    }
}
