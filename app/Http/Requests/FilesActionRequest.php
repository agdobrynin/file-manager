<?php

namespace App\Http\Requests;

use App\Models\File;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;

class FilesActionRequest extends ParentIdBaseRequest
{
    private const ALL_FILES_KEY = 'all';

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            self::ALL_FILES_KEY => 'nullable|boolean',
            'ids' => [
                'required_if:all,null,false',
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

    protected function prepareForValidation(): void
    {
        $this->merge([
            self::ALL_FILES_KEY => filter_var($this->{self::ALL_FILES_KEY}, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
        ]);
    }
}
