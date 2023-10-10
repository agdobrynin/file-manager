<?php

namespace App\Http\Requests;

use App\Models\FileShare;
use Illuminate\Contracts\Validation\ValidationRule;

class FileShareActionRequest extends ActionWithAllKeyRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'ids' => [
                'required_if:' . self::ALL_FILES_KEY . ',false',
                'array',
                function (string $attribute, array $ids, $fail) {
                    if ($ids) {
                        $fileSharCount = FileShare::fileShareForUserOrByUser($this->user())
                            ->whereIn('id', $ids)
                            ->count();

                        if ($fileSharCount !== count($ids)) {
                            $fail('Some file share IDs are not valid.');
                        }
                    }
                }
            ]
        ]);
    }
}
