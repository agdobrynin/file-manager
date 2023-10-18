<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\File;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class FileControllerMethodIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_not_auth_user_redirect_to_login(): void
    {
        $this->followingRedirects()->get('/file')
            ->assertOk()
            ->assertInertia(fn(AssertableInertia $page) => $page
                ->component('Auth/Login')
                ->where('auth.user', null)
            );
    }

    public function test_user_not_verified(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->followingRedirects()
            ->get('/file')
            ->assertOk()
            ->assertInertia(fn(AssertableInertia $page) => $page
                ->component('Auth/VerifyEmail')
                ->has('auth.user.name')
                ->where('auth.user.email', $user->email)
                ->where('auth.user.email_verified_at', null)
            );
    }

    public function test_user_with_empty_files(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $root = File::makeRootByUser($user);

        $this->get('/file')
            ->assertOk()
            ->assertInertia(fn(AssertableInertia $page) => $page
                ->component('MyFiles')
                ->has('ancestors.data.0', function (AssertableInertia $item) use ($root) {
                    $item->where('parentId', null)
                        ->where('id', $root->id)
                        ->where('isFolder', true)
                        ->etc();
                })
                ->where('auth.user.id', $user->id)
                // list of files is empty
                ->has('files.data', 0)
                // has pagination info
                ->has('files.links.next')
                ->has('files.meta.total')
            );
    }

    public function test_user_without_root_folder(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/file')
            ->assertNotFound();
    }
}
