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
use LogicException;
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

        $zipFile = Str::random(32).'.zip';
        $storagePath = '/var/tmp/'.$zipFile;

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
        MockInterface $mockArchive = null,
        MockInterface $mockFileContent = null,
        MockInterface $mockStorage = null
    ): FilesArchive {
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
            'is_folder' => false, 'name' => 'file3.jpg', 'storage_path' => '/f/333.jpg', 'disk' => DiskEnum::LOCAL,
        ], $root);

        $zipFile = Str::random(32).'.zip';
        $storagePath = '/var/tmp/'.$zipFile;

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
                    ->with('file3.jpg', 'file-content-as-string')
                    ->andReturnTrue();

                $mock->shouldReceive('addFromString')
                    ->with('folder/file2.jpg', 'file-content-as-string')
                    ->andReturnTrue();

                $mock->shouldReceive('addFromString')
                    ->with('folder/file1.jpg', 'file-content-as-string')
                    ->andReturnTrue();

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

        $downloadFile = ($this->makeService(
            mockArchive: $mockArchive,
            mockFileContent: $mockFileContent,
            mockStorage: $mockStorage,
        ))->addFiles($root->children);

        $this->assertEquals($storagePath, $downloadFile);
    }

    public function test_file_content_for_archive_sub_folder(): void
    {
        $user = User::factory()->create();
        Auth::shouldReceive('id')->andReturn($user->id);

        $root = File::makeRootByUser($user);

        $subFolder = File::create([
            'is_folder' => true, 'name' => 'folder', 'disk' => DiskEnum::LOCAL,
            'children' => [
                ['is_folder' => false, 'name' => 'file1.jpg', 'storage_path' => '/f/111.jpg', 'disk' => DiskEnum::LOCAL],
                ['is_folder' => false, 'name' => 'file2.jpg', 'storage_path' => '/f/222.jpg', 'disk' => DiskEnum::LOCAL],
            ],
        ], $root);
        File::create([
            'is_folder' => true, 'name' => 'folder2', 'disk' => DiskEnum::LOCAL,
            'children' => [
                ['is_folder' => false, 'name' => 'file3.jpg', 'storage_path' => '/f/333.jpg', 'disk' => DiskEnum::LOCAL],
                ['is_folder' => false, 'name' => 'file4.jpg', 'storage_path' => '/f/444.jpg', 'disk' => DiskEnum::LOCAL],
            ],
        ], $subFolder);
        $subFolder->refresh();

        $mockStorage = $this->partialMock(
            StorageLocalServiceInterface::class,
            fn (MockInterface $mock) => $mock->shouldReceive('filesystem->path')->andReturn('/a/aaa.zip')
        );

        $mockArchive = $this->getMockBuilder(ZipArchive::class)
            ->onlyMethods(['addFromString'])
            ->getMock();
        $mockArchive->expects(self::exactly(4))
            ->method('addFromString')
            ->willReturnCallback(function ($fileName) {
                // Files relative path in archive.
                if (! in_array($fileName, ['file1.jpg', 'file2.jpg', 'folder2/file3.jpg', 'folder2/file4.jpg'])) {
                    throw new LogicException('Unexpected file '.$fileName.' in archive');
                }

                return true;
            });

        $mockFileContent = $this->mock(
            GetFileContentInterface::class,
            fn (MockInterface $mock) => $mock->shouldReceive('getContent')
                ->times(4)->withAnyArgs()->andReturn('file-content-as-string')
        );

        $downloadFile = (new FilesArchive(archive: $mockArchive, localService: $mockStorage, fileContent: $mockFileContent))
            ->addFiles($subFolder->children);

        $this->assertEquals('/a/aaa.zip', $downloadFile);
    }
}
