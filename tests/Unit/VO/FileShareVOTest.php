<?php

declare(strict_types=1);

namespace Tests\Unit\VO;

use App\Models\File;
use App\Models\User;
use App\VO\FileShareVO;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class FileShareVOTest extends TestCase
{
    /**
     * A basic unit test example.
     */
    public function test_file_share_v_o(): void
    {
        $user = new User();
        $user->id = 2;
        $file = new File();
        $file->id = 1;

        $vo = new FileShareVO($user, $file);

        $this->assertArrayHasKey('file_id', $vo->toArray());
        $this->assertArrayHasKey('for_user_id', $vo->toArray());
        $this->assertInstanceOf(DateTimeImmutable::class, $vo->toArray()['created_at']);
        $this->assertInstanceOf(DateTimeImmutable::class, $vo->toArray()['updated_at']);
    }
}
