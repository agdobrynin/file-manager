<?php

namespace Tests\Unit\VO;

use App\Enums\DiskEnum;
use App\VO\FileFolderVO;
use PHPUnit\Framework\TestCase;

class FileFolderVOTest extends TestCase
{
    public function test_file_folder_v_o(): void
    {
        $vo = new FileFolderVO('my folder');
        $this->assertEquals(
            ['name' => 'my folder', 'is_folder' => true, 'disk' => DiskEnum::LOCAL],
            $vo->toArray()
        );
    }
}
