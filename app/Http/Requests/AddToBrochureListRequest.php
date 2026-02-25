<?php

namespace App\Http\Requests;

class AddToBrochureListRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalize form input: ensure item_codes/id_arr are arrays, and convert
     * checkbox "save" value ("on") to boolean for validation.
     */
    protected function prepareForValidation(): void
    {
        $merge = [];

        if ($this->has('item_codes') && ! is_array($this->item_codes)) {
            $merge['item_codes'] = $this->item_codes === null || $this->item_codes === ''
                ? []
                : [(string) $this->item_codes];
        }

        if ($this->has('id_arr') && ! is_array($this->id_arr)) {
            $merge['id_arr'] = $this->id_arr === null || $this->id_arr === ''
                ? []
                : [(string) $this->id_arr];
        }

        if ($this->has('save')) {
            $save = $this->save;
            $merge['save'] = filter_var($save, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? (bool) $save;
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }

    public function rules(): array
    {
        return [
            'item_codes' => ['nullable', 'array'],
            'item_codes.*' => ['nullable', 'string'],
            'fitting_type' => ['nullable', 'array'],
            'fitting_type.*' => ['nullable', 'string'],
            'location' => ['nullable', 'array'],
            'location.*' => ['nullable', 'string'],
            'description' => ['nullable', 'array'],
            'description.*' => ['nullable', 'string'],
            'item_name' => ['nullable', 'array'],
            'item_name.*' => ['nullable', 'string'],
            'project' => ['nullable', 'string', 'max:255'],
            'customer' => ['nullable', 'string', 'max:255'],
            'save' => ['nullable', 'boolean'],
            'id_arr' => ['nullable', 'array'],
            'id_arr.*' => ['nullable', 'string'],
            'generate_page' => ['nullable'],
        ];
    }
}
