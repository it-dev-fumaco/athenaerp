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
            'project' => ['required', 'string'],
            'item_code' => ['required', 'string'],
            'image_idx' => ['required', 'string'],
            'existing' => ['nullable', 'boolean'],
            'selected_image' => ['required_if:existing,1', 'nullable', 'string'],
            'selected-file' => ['required_if:existing,0', 'nullable', 'file', 'mimes:'.$allowed],
        ];
    }

    public function messages(): array
    {
        return [
            'selected-file.required_if' => 'No image was provided.',
            'selected-file.mimes' => 'Sorry, only .jpeg, .jpg and .png files are allowed.',
        ];
    }
}
