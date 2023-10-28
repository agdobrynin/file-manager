<?php

declare(strict_types=1);

namespace Tests\Unit\VO;

use App\Models\File;
use App\VO\DownloadFileVO;
use App\VO\Exception\DownloadFileCollectionEmpty;
use App\VO\Exception\DownloadFileNotFound;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class DownloadFileVOTest extends TestCase
{
    public static function data(): \Generator
    {
        yield 'empty files collection' => [
            'files' => collect([]),
            'exception' => DownloadFileCollectionEmpty::class,
        ];

        yield 'download file not exist' => [
            'files' => collect([new File()]),
            'exception' => DownloadFileNotFound::class,
        ];

        $file = tempnam(sys_get_temp_dir(), 'FOO');

        yield 'download single file' => [
            'files' => collect([new File(['is_folder' => false, 'name' => 'image.gif'])]),
            'exception' => '',
            'downloadFile' => $file,
            'expectName' => 'image.gif',
        ];

        $file = tempnam(sys_get_temp_dir(), 'FOO');
        rename($file, $file.'.zip');

        yield 'download single folder' => [
            'files' => collect([new File(['is_folder' => true, 'name' => 'My folder'])]),
            'exception' => '',
            'downloadFile' => $file.'.zip',
            'expectName' => 'My folder.zip',
        ];

        $file = tempnam(sys_get_temp_dir(), 'FOO');
        rename($file, $file.'.zip');

        $parent = new File(['is_folder' => true, 'name' => 'Sub folder']);
        $parent->setParentId(1);

        $collection = collect([
            new File(['is_folder' => false, 'name' => 'file1.doc']),
            new File(['is_folder' => false, 'name' => 'image.gif']),
            new File(['is_folder' => true, 'name' => 'My folder']),
        ]);

        foreach ($collection as $item) {
            $item->setRelation('parent', $parent);
        }

        yield 'download many files' => [
            'files' => $collection,
            'exception' => '',
            'downloadFile' => $file.'.zip',
            'expectName' => 'Sub folder.zip',
            'defaultName' => 'my files',
        ];
    }

    /**
     * @dataProvider data
     */
    public function test_download_value_object(
        Collection $files,
        string $exception = '',
        string $downloadFile = '',
        string $expectName = '',
        string $defaultName = '',
    ): void {
        if ($exception) {
            $this->expectException($exception);
        }

        $vo = new DownloadFileVO(
            files: $files,
            downloadFile: $downloadFile,
            defaultFileName: $defaultName,
        );

        $this->assertEquals($downloadFile, $vo->downloadFile);
        $this->assertEquals($expectName, $vo->fileName);
    }
}
