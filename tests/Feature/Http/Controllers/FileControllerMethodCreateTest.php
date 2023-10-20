<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class FileControllerMethodCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_not_auth_user(): void
    {
        $this->followingRedirects()->get('/file/create')
            ->assertOk()
            ->assertInertia(fn(AssertableInertia $page) => $page
                ->component('Auth/Login')
                ->url('/login')
                ->where('auth.user', null)
            );
    }

    public function test_not_verified_email(): void
    {
        $user = User::factory()->unverified()->create();

        $this->followingRedirects()->actingAs($user)->get('/file/create')
            ->assertOk()
            ->assertInertia(fn(AssertableInertia $page) => $page
                ->component('Auth/VerifyEmail')
                ->url('/verify-email')
                ->where('auth.user.id', $user->id)
            );
    }
}
