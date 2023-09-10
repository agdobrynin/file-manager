<?php

namespace App\Http\Requests;

use App\Helpers\PhpConfig;
use App\Models\File;
use Closure;
use Illuminate\Http\UploadedFile;

class FileUploadRequest extends ParentIdBaseRequest
{
    protected const RELATIVE_PATHS_KEY = 'relativePaths';

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            self::RELATIVE_PATHS_KEY => [
                'required',
                'array',
                function (string $attribute, array $values, Closure $fail) {
                    if (($max = PhpConfig::maxUploadFiles()) && $max < count($values)) {
                        $fail('Maximum available ' . $max . ' files for upload.');
                    }
                },
            ],
            self::RELATIVE_PATHS_KEY . '.*' => [
                'string',
                function (string $attribute, string $value, Closure $fail) {
                    if (($folder = explode('/', $value)[0])
                        && File::isUniqueName($folder, $this->user(), $this->parentFolder)) {
                        $fail('Folder "' . $folder . '" already exist for file ' . $value);
                    }
                },
            ],
            'files.*' => [
                'required',
                'file',
                function (string $attribute, mixed $value, Closure $fail) {
                    /** @var UploadedFile $value */
                    $name = $value->getClientOriginalName();

                    if (in_array($name, $this->input(self::RELATIVE_PATHS_KEY), true)
                        && File::isUniqueName($name, $this->user(), $this->parentFolder)) {
                        $fail('File "' . $name . '" already exist.');
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
        // Remove lead slash.
        $relativePathsFixed = array_map(static fn($item) => ltrim($item, '/'), $this->{self::RELATIVE_PATHS_KEY});

        $this->merge([
            self::RELATIVE_PATHS_KEY => $relativePathsFixed,
        ]);
    }
}
