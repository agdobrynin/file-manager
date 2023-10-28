<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ActionWithAllKeyRequest extends FormRequest
{
    protected const ALL_FILES_KEY = 'all';

    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    public function rules(): array
    {
        return [
            self::ALL_FILES_KEY => 'required|boolean',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            self::ALL_FILES_KEY => filter_var($this->{self::ALL_FILES_KEY}, FILTER_VALIDATE_BOOLEAN),
        ]);
    }
}
