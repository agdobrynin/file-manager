<?php

declare(strict_types=1);

namespace App\VO;

use Illuminate\Http\UploadedFile;
use Throwable;

readonly class UploadFilesVO
{
    /**
     * @var array<string, UploadedFile|array<string|UploadedFile>>|null
     */
    public array $tree;

    /**
     * @throws Throwable
     */
    public function __construct(
        array $files,
        array $relativePaths,
    ) {
        throw_unless(
            count($files) === count($relativePaths),
            message: 'The number of elements of the input arrays must be equal to.'
        );

        foreach ($files as $file) {
            throw_unless(
                $file instanceof UploadedFile,
                message: 'All uploaded file must be instance of '.UploadedFile::class
            );
        }

        $this->tree = $this->makeTree($files, $relativePaths);
    }

    protected function makeTree(array $files, array $paths): array
    {
        $tree = [];

        foreach ($paths as $ind => $path) {
            $parts = explode('/', $path);

            $currentNode = &$tree;

            foreach ($parts as $i => $part) {
                if (! isset($currentNode[$part])) {
                    $currentNode[$part] = [];
                }

                if ($i === count($parts) - 1) {
                    $currentNode[$part] = $files[$ind];
                } else {
                    $currentNode = &$currentNode[$part];
                }

            }
        }

        return $tree;
    }
}
