<?php

namespace App\Http\Requests;

use App\Helpers\PhpConfig;
use App\Models\File;
use Closure;
use Illuminate\Http\UploadedFile;

class FileUploadRequest extends ParentIdBaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'files.*' => [
                'required',
                'file',
                function (string $attribute, mixed $value, Closure $fail) {
                    /** @var UploadedFile $value */
                    $name = $value->getClientOriginalName();

                    if (File::isUniqueName($name, $this->user(), $this->parentFolder)) {
                        $fail('File "' . $name . '" already exist.');
                    }
                },
            ],
            'relativePaths' => [
                'required',
                'array',
                function (string $attr, array $values, Closure $fail) {
                    if (($max = PhpConfig::maxUploadFiles()) && $max < count($values)) {
                        $fail('Maximum available ' . $max . ' files for upload.');
                    }
                },
            ],
            'folderName' => [
                'nullable',
                'string',
                'min:1',
                function (string $attr, ?string $value, Closure $fail) {
                    if ($value) {
                        if (File::isUniqueName($value, $this->user(), $this->parentFolder)) {
                            $fail('Folder "' . $value . '" already exist.');
                        }
                    }
                },
            ],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'folderName' => explode('/', $this->relativePaths[0])[0] ?: null
        ]);
    }
}
