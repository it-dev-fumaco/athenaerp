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
            'selected-file' => ['required', 'file', 'mimes:'.$allowed, 'max:10240'],
            'project' => ['required', 'string', 'max:255'],
            'filename' => ['required', 'string', 'max:255'],
            'row' => ['required', 'numeric', 'min:1'],
            'column' => ['required', 'string', 'max:255'],
            'item_image_id' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'selected-file.required' => 'No file selected.',
            'selected-file.mimes' => 'Sorry, only .jpeg, .jpg, .png and .webp files are allowed.',
            'selected-file.max' => 'The image may not be greater than 10 MB.',
        ];
    }
}
