<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Contracts\StorageByDiskTypeServiceInterface;
use App\Contracts\StorageServiceInterface;
use App\Dto\DestroyFileFromStorageDto;
use App\Enums\DiskEnum;
use App\Jobs\DeleteFileFromStorageJob;
use Illuminate\Contracts\Filesystem\Filesystem;
use PHPUnit\Framework\TestCase;

class DeleteFileFromStorageJobTest extends TestCase
{
    public function test_resolve_by_disk(): void
    {
        $dto = new DestroyFileFromStorageDto(
            DiskEnum::CLOUD,
            '/file/aaa.png'
        );

        $mockFilesystem = $this->createMock(Filesystem::class);
        $mockFilesystem->expects(self::once())
            ->method('delete')
            ->with($dto->fileStoragePath);

        $mockStorage = $this->createMock(StorageServiceInterface::class);
        $mockStorage->expects(self::once())
            ->method('filesystem')
            ->willReturn($mockFilesystem);

        $mockSrv = $this->createMock(StorageByDiskTypeServiceInterface::class);
        $mockSrv->expects(self::once())
            ->method('resolve')
            ->with($dto->disk)
            ->willReturn($mockStorage);

        (new DeleteFileFromStorageJob($dto))->handle($mockSrv);
    }
}
