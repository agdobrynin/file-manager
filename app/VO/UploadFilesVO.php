<?php
declare(strict_types=1);

namespace App\VO;

use Illuminate\Http\UploadedFile;
use Throwable;

readonly class UploadFilesVO
{
    /**
     * @var UploadedFile[]|null
     */
    public ?array $files;
    /**
     * @var array<string, array<string|UploadedFile, string|UploadedFile>>|null
     */
    public ?array $tree;

    /**
     * @throws Throwable
     */
    public function __construct(
        array   $files,
        array   $relativePaths,
        ?string $folderName,
    )
    {
        foreach ($files as $file) {
            throw_unless($file instanceof UploadedFile, message: 'All uploaded file must be instance of ' . UploadedFile::class);
        }

        if ($folderName) {
            $this->tree = $this->makeTree($files, $relativePaths);
            $this->files = null;
        } else {
            $this->files = $files;
            $this->tree = null;
        }
    }

    protected function makeTree(array $files, array $paths): array
    {
        $filePaths = array_slice($paths, 0, count($files));
        $filePaths = array_filter($filePaths, static fn($f) => $f !== null);

        $tree = [];

        foreach ($filePaths as $ind => $filePath) {
            $parts = explode('/', $filePath);

            $currentNode = &$tree;

            foreach ($parts as $i => $part) {
                if (!isset($currentNode[$part])) {
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
