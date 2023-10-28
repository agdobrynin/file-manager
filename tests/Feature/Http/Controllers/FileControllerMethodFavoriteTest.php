<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Models\File;
use App\Models\FileFavorite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class FileControllerMethodFavoriteTest extends TestCase
{
    use RefreshDatabase;

    public function test_id_is_not_found(): void
    {
        $this->actingAs(User::factory()->create())
            ->patch('/file/favorite', ['id' => 1000])
            ->assertRedirect()
            ->assertSessionHasErrors('id');
    }

    public function test_id_other_user(): void
    {
        $user = User::factory()->create();
        $file = File::factory()
            ->isFile(User::factory()->create())
            ->createQuietly();

        $this->actingAs($user)
            ->patch('/file/favorite', ['id' => $file->id])
            ->assertRedirect()
            ->assertSessionHasErrors('id');
    }

    public function test_mark_as_favorite_success(): void
    {
        $user = User::factory()->create();
        $file = File::factory()
            ->isFile($user)
            ->createQuietly();

        $this->assertDatabaseMissing(FileFavorite::class, ['file_id' => $file->id]);

        $this->actingAs($user)
            ->patch('/file/favorite', ['id' => $file->id])
            ->assertRedirect()
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $this->assertDatabaseHas(FileFavorite::class, ['file_id' => $file->id]);
    }

    public function test_not_auth_user(): void
    {
        $this->followingRedirects()->patch('/file/favorite')
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->component('Auth/Login')
                    ->url('/login')
                    ->where('auth.user', null)
            );
    }

    public function test_not_user_unverified_email(): void
    {
        $this->followingRedirects()
            ->actingAs(User::factory()->unverified()->create())
            ->patch('/file/favorite')
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->component('Auth/VerifyEmail')
                    ->url('/verify-email')
                    ->whereType('auth.user.id', 'integer')
            );
    }

    public function test_unmark_as_favorite_success(): void
    {
        $user = User::factory()->create();
        $file = File::factory()
            ->has(
                FileFavorite::factory()->for($user),
                'favorite'
            )
            ->isFile($user)
            ->createQuietly();

        $this->assertDatabaseHas(FileFavorite::class, ['file_id' => $file->id]);

        $this->actingAs($user)
            ->patch('/file/favorite', ['id' => $file->id])
            ->assertRedirect()
            ->assertSessionHasNoErrors()
            ->assertSessionHas('info');

        $this->assertDatabaseMissing(FileFavorite::class, ['file_id' => $file->id]);
    }
}
