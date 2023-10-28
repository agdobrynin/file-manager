<?php

namespace App\Http\Requests;

use App\Models\File;
use Closure;

class FileUploadRequest extends ParentIdBaseRequest
{
    protected const RELATIVE_PATHS_KEY = 'relativePaths';

    protected const FILES_KEY = 'files';

    protected $stopOnFirstFailure = true;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        $this->redirect = $this->redirector
            ->getUrlGenerator()
            ->route('file.index', ['parentFolder' => $this->parentFolder]);

        return [
            self::FILES_KEY => [
                'required',
                'array',
            ],
            self::FILES_KEY.'.*' => [
                'required',
                'file',
            ],
            self::RELATIVE_PATHS_KEY => [
                'required',
                'array',
                function (string $attribute, array $values, Closure $fail) {
                    $maxCountFiles = config('upload_files.upload.max_files');

                    if ($maxCountFiles < count($values)) {
                        $fail('Maximum available '.$maxCountFiles.' files for upload.');

                        return;
                    }

                    $firstLevelFolders = collect();
                    $firstLevelFiles = collect();

                    foreach ($values as $index => $path) {
                        $firstPathOfPath = explode('/', $path)[0];

                        if ($firstPathOfPath === $path) {
                            $name = $this->allFiles()[self::FILES_KEY][$index]->getClientOriginalName();
                            $firstLevelFiles->add($name);
                        }

                        if ($firstPathOfPath !== $path) {
                            $firstLevelFolders->add($firstPathOfPath);
                        }
                    }

                    $parentFolder = $this->parentFolder ?: File::rootFolderByUser($this->user());

                    $folders = File::existNames($firstLevelFolders->unique()->toArray(), $this->user(), $parentFolder);

                    foreach ($folders->pluck('name')->toArray() as $index => $folder) {
                        $this->validator->errors()->add('folder.'.$index, 'Folder "'.$folder.'" already exist');
                    }

                    $files = File::existNames($firstLevelFiles->unique()->toArray(), $this->user(), $parentFolder);

                    foreach ($files->pluck('name')->toArray() as $index => $file) {
                        $this->validator->errors()->add('file.'.$index, 'File "'.$file.'" already exist');
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
        $relativePathsFixed = is_array($this->{self::RELATIVE_PATHS_KEY})
            ? array_map(static fn ($item) => ltrim($item, '/'), $this->{self::RELATIVE_PATHS_KEY})
            : [];

        $this->merge([
            self::RELATIVE_PATHS_KEY => $relativePathsFixed,
        ]);
    }
}
