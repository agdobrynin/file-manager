<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\File;
use App\Models\FileFavorite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FileFavoriteTest extends TestCase
{
    use RefreshDatabase;

    public function test_relations(): void
    {
        $user = User::factory()->create();
        $folder = File::factory()->isFolder($user)
            ->createQuietly();

        $favorite = FileFavorite::factory()
            ->for($user)
            ->for($folder)
            ->create();

        $this->assertInstanceOf(User::class, $favorite->user()->first());
        $this->assertInstanceOf(File::class, $favorite->file()->first());
    }
}
