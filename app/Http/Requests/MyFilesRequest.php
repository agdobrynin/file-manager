<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MyFilesRequest extends ParentIdBaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'search' => 'nullable|string',
        ];
    }
}
