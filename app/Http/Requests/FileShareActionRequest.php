<?php

namespace App\Http\Requests;

use App\Models\FileShare;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class FileShareActionRequest extends FormRequest
{
    protected const ALL_FILES_KEY = 'all';

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

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
                'required_if:' . self::ALL_FILES_KEY . ',null,false',
                'array',
                function (string $attribute, array $ids, $fail) {
                    if ($ids) {
                        $fileSharCount = FileShare::fileShareByFileOwner($this->user())
                            ->whereIn('id', $ids)
                            ->count();

                        if ($fileSharCount !== count($ids)) {
                            $fail('Some file share IDs are not valid.');
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
