<?php

namespace Tests\Feature\Services;

use App\Contracts\GetFileContentInterface;
use App\Contracts\StorageLocalServiceInterface;
use App\Enums\DiskEnum;
use App\Models\File;
use App\Models\User;
use App\Services\Exceptions\FilesCollectionIsEmpty;
use App\Services\Exceptions\OpenArchiveException;
use App\Services\FilesArchive;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Mockery\MockInterface;
use Tests\TestCase;
use ZipArchive;

class FilesArchiveTest extends TestCase
{
    public function test_can_not_create_archive(): void
    {
        $user = User::factory()->create();

        $files = File::factory(2)
            ->forUser($user)
            ->createQuietly();

        $zipFile = Str::random(32) . '.zip';
        $storagePath = '/var/tmp/' . $zipFile;

        $mockStorage = $this->mock(
            StorageLocalServiceInterface::class,
            function (MockInterface $mock) use ($storagePath) {
                $mock->shouldReceive('filesystem->path')
                    ->andReturn($storagePath)
                    ->once();
            }
        );

        $mockArchive = $this->mock(
            ZipArchive::class,
            function (MockInterface $mock) use ($storagePath) {
                $mock->shouldReceive('open')
                    ->with($storagePath, ZipArchive::CREATE | ZipArchive::OVERWRITE)
                    ->andReturnFalse();
            }
        );

        $this->expectException(OpenArchiveException::class);

        ($this->makeService(
            mockArchive: $mockArchive,
            mockStorage: $mockStorage
        ))->addFiles($files);
    }

    protected function makeService(
        ?MockInterface $mockArchive = null,
        ?MockInterface $mockFileContent = null,
        ?MockInterface $mockStorage = null
    ): FilesArchive
    {
        $mockArchive = $mockArchive ?: $this->mock(ZipArchive::class);
        $mockFileContent = $mockFileContent ?: $this->mock(GetFileContentInterface::class);
        $mockStorage = $mockStorage ?: $this->mock(StorageLocalServiceInterface::class);

        return new FilesArchive(
            archive: $mockArchive,
            localService: $mockStorage,
            fileContent: $mockFileContent
        );
    }

    public function test_empty_collection(): void
    {
        $this->expectException(FilesCollectionIsEmpty::class);

        ($this->makeService())->addFiles(collect());
    }

    public function test_file_content_for_archive(): void
    {
        $user = User::factory()->create();
        Auth::shouldReceive('id')->andReturn($user->id);

        $root = File::makeRootByUser($user);

        File::create([
            'is_folder' => true, 'name' => 'folder', 'disk' => DiskEnum::LOCAL,
            'children' => [
                ['is_folder' => false, 'name' => 'file1.jpg', 'storage_path' => '/f/111.jpg', 'disk' => DiskEnum::LOCAL],
                ['is_folder' => false, 'name' => 'file2.jpg', 'storage_path' => '/f/222.jpg', 'disk' => DiskEnum::LOCAL],
            ],
        ], $root);

        File::create([
            'is_folder' => false, 'name' => 'file3.jpg', 'storage_path' => '/f/333.jpg', 'disk' => DiskEnum::LOCAL
        ], $root);

        $zipFile = Str::random(32) . '.zip';
        $storagePath = '/var/tmp/' . $zipFile;

        $mockStorage = $this->mock(
            StorageLocalServiceInterface::class,
            function (MockInterface $mock) use ($storagePath) {
                $mock->shouldReceive('filesystem->path')
                    ->andReturn($storagePath)
                    ->once();
            }
        );

        $mockArchive = $this->mock(
            ZipArchive::class,
            function (MockInterface $mock) use ($storagePath) {
                $mock->shouldReceive('open')
                    ->with($storagePath, ZipArchive::CREATE | ZipArchive::OVERWRITE)
                    ->andReturnTrue();

                $mock->shouldReceive('addFromString')
                    ->withAnyArgs()
                    ->andReturnTrue()
                    ->times(3);

                $mock->shouldReceive('close');
            }
        );

        $mockFileContent = $this->mock(
            GetFileContentInterface::class,
            function (MockInterface $mock) {
                $mock->shouldReceive('getContent')
                    ->withAnyArgs()
                    ->andReturn('file-content-as-string');
            }
        );

        $dto = ($this->makeService(
            mockArchive: $mockArchive,
            mockFileContent: $mockFileContent,
            mockStorage: $mockStorage,
        ))->addFiles($root->children);

        $this->assertEquals($user->name . '.zip', $dto->fileName);
        $this->assertEquals($storagePath, $dto->storagePath);
    }
}
