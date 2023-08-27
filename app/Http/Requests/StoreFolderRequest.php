<?php

namespace App\Http\Requests;

use App\Models\File;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreFolderRequest extends ParentIdBaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            ...[
                'name' => [
                    'required',
                    Rule::unique(File::class, 'name')
                        ->where('created_by', Auth::id())
                        ->where('parent_id',  $this->parent?->id)
                        ->whereNull('deleted_at')
                ],
            ]
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'Folder ":input" already exist'
        ];
    }
}