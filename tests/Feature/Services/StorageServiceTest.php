<?php

namespace Tests\Feature\Services;

use App\Enums\DiskEnum;
use App\Services\StorageService;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StorageServiceTest extends TestCase
{
    public static function data(): \Generator
    {
        yield 'uploaded file' => [
            'disk' => DiskEnum::LOCAL,
            'contents' => UploadedFile::fake()->image('image.png'),
        ];

        yield 'string file' => [
            'disk' => DiskEnum::CLOUD,
            'contents' => 'aaaa',
        ];

        yield 'file file' => [
            'disk' => DiskEnum::LOCAL,
            'contents' => new File(__FILE__),
        ];
    }

    /** @dataProvider data */
    public function test_service(DiskEnum $disk, \Illuminate\Http\Testing\File|File|string $contents): void
    {
        $filesystem = Storage::fake('test');
        $srv = new StorageService($filesystem, $disk);

        $res = $srv->upload($contents, 'files/1');

        $this->assertInstanceOf(Filesystem::class, $srv->filesystem());
        $this->assertEquals($disk, $srv->disk());

        if (is_string($res)) {
            Storage::disk('test')->assertExists($res);
        } else {
            $this->assertTrue($res);
        }
    }
}
