<?php

namespace Tests\Feature\Services;

use App\Contracts\FilesArchiveInterface;
use App\Contracts\GetFileContentInterface;
use App\Contracts\StorageLocalServiceInterface;
use App\Models\File;
use App\Models\User;
use App\Services\Exceptions\DownloadEmptyFolderException;
use App\Services\Exceptions\PutFileForDownloadException;
use App\Services\MakeDownloadFilesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Mockery\MockInterface;
use Tests\TestCase;

class MakeDownloadFilesServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_not_copy_file(): void
    {
        $file = File::factory()->isFile(User::factory()->create())->createQuietly();
        $files = collect([$file]);

        $mockStorage = $this->mock(StorageLocalServiceInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('filesystem->put')
                ->andReturnFalse()
                ->once();
        });

        $mockGetFileContent = $this->mock(GetFileContentInterface::class, function (MockInterface $mock) use ($file) {
            $mock->shouldReceive('getContent')
                ->with($file)
                ->andReturn('file-content')
                ->once();
        });

        $this->expectException(PutFileForDownloadException::class);

        $this->makeSimpleService(
            storageMock: $mockStorage,
            fileContentMock: $mockGetFileContent
        )
            ->handle($files);
    }

    protected function makeSimpleService(
        MockInterface $archive = null,
        MockInterface $storageMock = null,
        MockInterface $fileContentMock = null,
    ): MakeDownloadFilesService {
        $archive = $archive ?: $this->mock(FilesArchiveInterface::class);
        $storage = $storageMock ?: $this->mock(StorageLocalServiceInterface::class);
        $fileContentMock = $fileContentMock ?: $this->mock(GetFileContentInterface::class);

        return new MakeDownloadFilesService(
            localService: $storage,
            fileContent: $fileContentMock,
            archive: $archive,
        );
    }

    public function test_download_archive(): void
    {
        $user = User::factory()->create();
        $file = File::factory()->isFile($user)->createQuietly();
        $file2 = File::factory()->isFile($user)->createQuietly();
        $files = collect([$file, $file2]);

        $mockStorage = $this->mock(StorageLocalServiceInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('filesystem->put')
                ->andReturnFalse()
                ->never();
        });

        $mockGetFileContent = $this->mock(GetFileContentInterface::class, function (MockInterface $mock) use ($file) {
            $mock->shouldReceive('getContent')
                ->with($file)
                ->andReturn('file-content')
                ->never();
        });

        $mockArchive = $this->mock(FilesArchiveInterface::class, function (MockInterface $mock) use ($files) {
            $mock->shouldReceive('addFiles')
                ->with($files)
                ->andReturn('/var/tmp/my.zip')
                ->once();
        });

        $this->makeSimpleService(
            archive: $mockArchive,
            storageMock: $mockStorage,
            fileContentMock: $mockGetFileContent,
        )
            ->handle($files);
    }

    public function test_download_single_file(): void
    {
        $file = File::factory()->isFile(User::factory()->create())->createQuietly();
        $files = collect([$file]);
        $fileTmpName = Str::random(40);

        $mockStorage = $this->mock(StorageLocalServiceInterface::class, function (MockInterface $mock) use ($fileTmpName) {
            $mock->shouldReceive('filesystem->put')
                ->andReturnTrue()
                ->once();
            $mock->shouldReceive('filesystem->path')
                ->andReturn($fileTmpName)
                ->once();
        });

        $fileContentMock = $this->mock(GetFileContentInterface::class, function (MockInterface $mock) use ($file) {
            $mock->shouldReceive('getContent')
                ->with($file)
                ->andReturn('content')
                ->once();
        });

        $downloadFile = $this->makeSimpleService(
            storageMock: $mockStorage,
            fileContentMock: $fileContentMock,
        )
            ->handle($files);

        $this->assertEquals($fileTmpName, $downloadFile);
    }

    public function test_empty_folder(): void
    {
        $file = File::factory()->isFolder(User::factory()->create())->createQuietly();
        $files = collect([$file]);

        $this->expectException(DownloadEmptyFolderException::class);
        $this->makeSimpleService()->handle($files);
    }

    public function test_no_files(): void
    {
        $files = collect();

        $this->expectException(\RuntimeException::class);
        $this->makeSimpleService()->handle($files);
    }
}
