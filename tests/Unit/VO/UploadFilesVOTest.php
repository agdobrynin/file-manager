<?php

namespace Tests\Unit\VO;

use App\VO\UploadFilesVO;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\TestCase;
use Throwable;

class UploadFilesVOTest extends TestCase
{
    public static function data(): \Generator
    {
        yield 'relative path count more then files' => [
            'files' => [UploadedFile::fake()->image('1.jpg')],
            'relativePaths' => ['1.jpg', '2.jpg'],
            'exception' => \RuntimeException::class,
        ];

        yield 'relative path count less then files' => [
            'files' => [
                UploadedFile::fake()->image('1.jpg'),
                UploadedFile::fake()->image('2.jpg'),
            ],
            'relativePaths' => ['1.jpg'],
            'exception' => \RuntimeException::class,
        ];

        $f1 = UploadedFile::fake()->image('1.jpg');
        $f2 = UploadedFile::fake()->image('2.jpg');
        $f3 = UploadedFile::fake()->image('1-1.jpg');
        $f4 = UploadedFile::fake()->image('1-2.jpg');
        $f5 = UploadedFile::fake()->image('2-1.jpg');

        yield 'success' => [
            'files' => [$f3, $f4, $f5, $f1, $f2],
            'relativePaths' => ['folder1/1-1.jpg', 'folder1/1-2.jpg', 'folder2/2-1.jpg', '1.jpg', '2.jpg'],
            'exception' => null,
            'tree' => [
                '1.jpg' => $f1,
                '2.jpg' => $f2,
                'folder1' => [
                    '1-1.jpg' => $f3,
                    '1-2.jpg' => $f4,
                ],
                'folder2' => [
                    '2-1.jpg' => $f5,
                ],
            ],
        ];
    }

    /** @dataProvider data
     * @throws Throwable
     */
    public function test_upload_files_vo(
        array $files,
        array $relativePaths,
        string $exception = null,
        array $tree = null,
    ): void {
        if ($exception) {
            $this->expectException($exception);
        }

        $vo = new UploadFilesVO($files, $relativePaths);

        if ($tree) {
            $this->assertEquals($tree, $vo->tree);
        }
    }
}
