<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\File;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FileControllerMethodShareTest extends TestCase
{
    use RefreshDatabase;

    public static function dataForValidation(): \Generator
    {
        yield 'all is undefined and ids not set' => [
            'data' => [],
            'errors' => ['email', 'ids' => 'The ids field is required when all is false.'],
            'noErrors' => ['all'],
        ];

        yield 'all is set false and ids not set' => [
            'data' => [
                'all' => false,
                'ids' => 1,
            ],
            'errors' => ['email', 'ids' => 'The ids field must be an array.'],
            'noErrors' => ['all'],
        ];

        yield 'all is set "true" parent param in route not set' => [
            'data' => [
                'all' => true,
            ],
            'errors' => ['email', 'all' => 'When parameter "all" is true route parameter "parentFolder" is required.'],
            'noErrors' => ['ids'],
        ];

        yield 'all is set "false" ids set is wrong' => [
            'data' => [
                'all' => false,
                'ids' => [10, 11, 12],
            ],
            'errors' => ['ids' => 'Some file IDs are not valid.', 'email'],
            'noErrors' => ['all'],
        ];

        yield 'all is set "false" ids set from other owner' => [
            'data' => [
                'all' => false,
                'ids' => fn() => File::factory(3)
                    ->isFile(User::factory()->create())
                    ->createQuietly()
                    ->pluck('id')
                    ->toArray()
                ,
            ],
            'errors' => ['ids' => 'Some file IDs are not valid.', 'email'],
            'noErrors' => ['all'],
        ];

        yield 'email with validation rule "email"' => [
            'data' => [
                'all' => false,
                'ids' => [],
                'email' => 'aaa'
            ],
            'errors' => ['email' => 'The email field must be a valid email address.'],
            'noErrors' => ['all'],
        ];

        yield 'email with validation rule "rfc", "strict"' => [
            'data' => [
                'all' => false,
                'ids' => [],
                'email' => 'aaa@aaa'
            ],
            'errors' => ['email' => 'The email field must be a valid email address.'],
            'noErrors' => ['all'],
        ];
    }

    /**
     * @dataProvider dataForValidation
     */
    public function test_validation(array $data, array $errors, array $noErrors): void
    {
        $user = User::factory()->create();

        if (($data['ids'] ?? null) instanceof \Closure) {
            $data['ids'] = $data['ids']();
            foreach ($data['ids'] as $id) {
                $this->assertDatabaseHas(File::class, ['id' => $id]);
            }
        }

        $this->actingAs($user)->post('/file/share/', $data)
            ->assertSessionHasErrors($errors)
            ->assertSessionDoesntHaveErrors($noErrors);
    }

    public function test_validation_can_not_share_files_to_yourself(): void
    {
        $user = User::factory()->create();
        $ids = File::factory(3)
            ->isFile($user)
            ->createQuietly()
            ->pluck('id')
            ->toArray();

        $this->actingAs($user)->post('/file/share/', ['ids' => $ids, 'email' => $user->email])
            ->assertSessionHasErrors(['email'])
            ->assertSessionDoesntHaveErrors(['ids', 'all']);
    }
}
