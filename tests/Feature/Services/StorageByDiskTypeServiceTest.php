<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Contracts\StorageCloudServiceInterface;
use App\Contracts\StorageLocalServiceInterface;
use App\Enums\DiskEnum;
use App\Services\StorageByDiskTypeService;
use Tests\TestCase;

class StorageByDiskTypeServiceTest extends TestCase
{
    public static function data(): \Generator
    {
        yield 'resolve Local storage' => [
            'disk' => DiskEnum::LOCAL,
            'expect' => StorageLocalServiceInterface::class,
        ];

        yield 'resolve Cloud storage' => [
            'disk' => DiskEnum::CLOUD,
            'expect' => StorageCloudServiceInterface::class,
        ];
    }

    /** @dataProvider data */
    public function test_example(DiskEnum $disk, string $expect): void
    {
        $local = $this->mock(StorageLocalServiceInterface::class);
        $cloud = $this->mock(StorageCloudServiceInterface::class);

        $srv = new StorageByDiskTypeService($local, $cloud);
        $storage = $srv->resolve($disk);

        $this->assertInstanceOf($expect, $storage);
    }
}
