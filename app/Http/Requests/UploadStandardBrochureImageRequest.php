<?php

namespace App\Http\Requests;

class UploadStandardBrochureImageRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Normalize "existing" so validation rules work when form omits it (e.g. upload-new-image form)
        $input = $this->all();
        if (! array_key_exists('existing', $input)) {
            $this->merge(['existing' => 0]);
        }
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
            'project.required' => 'Project name is required.',
            'item_code.required' => 'Item code is required.',
            'image_idx.required' => 'Image slot (image_idx) is required.',
            'selected-file.required_unless' => 'No image was provided. Please select a file to upload.',
            'selected-file.mimes' => 'Sorry, only .jpeg, .jpg, .png and .webp files are allowed.',
            'selected-file.max' => 'The image may not be greater than 10 MB.',
        ];
    }
}
