<?php

namespace Tests\Feature\Models;

use App\Models\File;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FileScopeFileByOwnerTest extends TestCase
{
    use RefreshDatabase;

    public function test_file_by_owner(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        File::factory(5)->isFile($user1)
            ->createQuietly();

        File::factory(10)->isFile($user2)
            ->createQuietly();

        $forUser1 = File::fileByOwner($user1);
        $this->assertInstanceOf(Builder::class, $forUser1);
        $this->assertCount(5, $forUser1->get());
        $this->assertInstanceOf(File::class, $forUser1->get()->first());

        $forUser2 = File::fileByOwner($user2);
        $this->assertInstanceOf(Builder::class, $forUser2);
        $this->assertCount(10, $forUser2->get());
    }
}
