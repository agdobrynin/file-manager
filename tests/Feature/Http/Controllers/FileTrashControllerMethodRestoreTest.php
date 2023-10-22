<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FileTrashControllerMethodRestoreTest extends TestCase
{
    use RefreshDatabase;

    public static function dataMethodNotAllowed(): \Generator
    {
        yield 'method get' => ['get'];
        yield 'method delete' => ['delete'];
        yield 'method patch' => ['patch'];
        yield 'method put' => ['put'];
    }

    /**
     * @dataProvider dataMethodNotAllowed
     */
    public function test_method_not_allowed(string $method): void
    {
        $this->actingAs(User::factory()->create())
            ->{$method}('/trash/restore')
            ->assertMethodNotAllowed();
    }
}
