<?php

namespace App\Http\Requests;

class FilesActionRequest extends ParentIdBaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'allFiles' => 'nullable|boolean',
            'fileIds' => [
                'required_if:allFiles,null,false',
                'array',
            ]
        ];
    }
}
