<?php

namespace App\Http\Requests;

use App\Helpers\PhpConfig;
use App\Models\File;
use Closure;
use Illuminate\Support\Str;

class FileUploadRequest extends ParentIdBaseRequest
{
    protected const RELATIVE_PATHS_KEY = 'relativePaths';
    protected const FILES_KEY = 'files';

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            self::FILES_KEY . '.*' => [
                'required',
                'file',
            ],
            self::RELATIVE_PATHS_KEY => [
                'required',
                'array',
                function (string $attribute, array $values, Closure $fail) {
                    if (($max = PhpConfig::maxUploadFiles()) && $max < count($values)) {
                        $fail('Maximum available ' . $max . ' files for upload.');
                    }

                    $firstLevelFolders = collect();
                    $firstLevelFiles = collect();

                    foreach ($this->input(self::RELATIVE_PATHS_KEY) as $index => $path) {
                        $firstPathOfPath = explode('/', $path)[0];

                        if ($firstPathOfPath === $path) {
                            $name = $this->allFiles()[self::FILES_KEY][$index]->getClientOriginalName();
                            $firstLevelFiles->add($name);
                        }

                        if ($firstPathOfPath !== $path) {
                            $firstLevelFolders->add($firstPathOfPath);
                        }
                    }

                    $folders = File::existNames($firstLevelFolders->unique()->toArray(), $this->user(), $this->parentFolder);

                    if ($names = $folders->pluck('name')->implode(', ')) {
                        $fail(Str::plural('Folder', $folders->count()) . ' "' . $names . '" already exist');
                    }

                    $files = File::existNames($firstLevelFiles->unique()->toArray(), $this->user(), $this->parentFolder);

                    if ($names = $files->pluck('name')->implode(', ')) {
                        $fail(Str::plural('File', $files->count()) . ' "' . $names . '" already exist');
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
