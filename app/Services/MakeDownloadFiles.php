<?php
declare(strict_types=1);

namespace App\Services;

use App\Contracts\StorageCloudServiceInterface;
use App\Contracts\StorageLocalServiceInterface;
use App\Contracts\StorageServiceInterface;
use App\Dto\DownloadFileDto;
use App\Enums\DiskEnum;
use App\Models\File;
use Illuminate\Database\Eloquent\Collection;

readonly class MakeDownloadFiles
{
    public function __construct(
        private StorageCloudServiceInterface $cloudService,
        private StorageLocalServiceInterface $localService,
    )
    {
    }

    /**
     * @param Collection<File> $files
     */
    public function handle(Collection $files): DownloadFileDto
    {
        throw new \LogicException('Not implemented yet.');
    }

    private function getContent(File $file): string
    {
        /** @var StorageServiceInterface $storage */
        $storage = match ($file->disk) {
            DiskEnum::LOCAL => $this->localService,
            DiskEnum::CLOUD => $this->cloudService,
        };

        return $storage->filesystem()->get($file->path);
    }
}
