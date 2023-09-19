<?php

namespace App\Http\Requests;

use App\Models\File;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;

class FilesActionRequest extends ParentIdBaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'allFiles' => 'nullable|boolean',
            'fileIds' => [
                'required_if:allFiles,null,false',
                'array',
                function (string $attribute, array $ids, $fail) {
                    foreach ($ids as $id) {
                        $file = File::query()->where('id', $id)
                            ->where('created_by', Auth::id())
                            ->first();

                        if (null === $file) {
                            $fail('Invalid file ID ' . $id . ' for auth user.');
                        }
                    }
                }
            ]
        ];
    }
}
