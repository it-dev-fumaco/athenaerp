<?php

namespace App\Http\Requests;

class UploadStandardBrochureImageRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $allowed = implode(',', ['jpg', 'jpeg', 'png', 'webp']);

        return [
            'project' => ['required', 'string', 'max:255'],
            'item_code' => ['required', 'string', 'max:255'],
            'image_idx' => ['required', 'string', 'max:50'],
            'existing' => ['nullable', 'boolean'],
            'selected_image' => ['required_if:existing,1', 'nullable', 'string'],
            'selected-file' => ['required_unless:existing,1', 'nullable', 'file', 'mimes:'.$allowed, 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'selected-file.required_unless' => 'No image was provided.',
            'selected-file.mimes' => 'Sorry, only .jpeg, .jpg, .png and .webp files are allowed.',
            'selected-file.max' => 'The image may not be greater than 10 MB.',
        ];
    }
}
