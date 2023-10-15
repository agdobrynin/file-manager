<?php

namespace Tests\Unit\VO;

use App\Enums\DiskEnum;
use App\VO\FileVO;
use PHPUnit\Framework\TestCase;

class FileVOTest extends TestCase
{
    public static function data(): \Generator
    {
        yield 'set #1' => [
            'vo' => new FileVO(
                name: 'my-file.jpg',
                mime: 'image/jpeg',
                size: 100,
            ),
            'expect' => [
                'name' => 'my-file.jpg',
                'is_folder' => false,
                'mime' => 'image/jpeg',
                'size' => 100,
                'disk' => DiskEnum::LOCAL,
                'path' => null,
                'storage_path' => null,
            ]
        ];

        yield 'set #2' => [
            'vo' => new FileVO(
                name: 'my-file.jpg',
                mime: 'image/jpeg',
                size: 100,
                path: '/my-file.jpg',
                storagePath: '/files/1/QweRed.jpg',
                disk: DiskEnum::CLOUD
            ),
            'expect' => [
                'name' => 'my-file.jpg',
                'is_folder' => false,
                'mime' => 'image/jpeg',
                'size' => 100,
                'disk' => DiskEnum::CLOUD,
                'path' => '/my-file.jpg',
                'storage_path' => '/files/1/QweRed.jpg',
            ]
        ];
    }

    /**
     * @dataProvider data
     */
    public function test_file_v_o(FileVO $vo, array $expect): void
    {
        $this->assertEquals($expect, $vo->toArray());
    }
}
