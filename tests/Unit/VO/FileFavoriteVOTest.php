<?php

namespace Tests\Unit\VO;

use App\VO\FileFavoriteVO;
use PHPUnit\Framework\TestCase;

class FileFavoriteVOTest extends TestCase
{
    public function test_file_favorite_v_o(): void
    {
        $vo = new FileFavoriteVO(1, 1);

        $this->assertEquals(['file_id' => 1, 'user_id' => 1], $vo->toArray());
    }
}
