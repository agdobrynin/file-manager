<?php

namespace Tests\Feature\Http\Requests;

use App\Http\Requests\FilesListFilterRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class FilesListFilterRequestTest extends TestCase
{
    use RefreshDatabase;

    public static function data(): \Generator
    {
        yield 'empty' => [
            'data' => [],
            'user' => fn() => User::factory()->create(),
            'authorize' => true,
            'passes' => true,
        ];

        yield 'user not auth' => [
            'data' => [],
            'user' => fn() => null,
            'authorize' => false,
            'passes' => true,
        ];

        yield 'with search' => [
            'data' => ['search' => 'my-file'],
            'user' => fn() => User::factory()->create(),
            'authorize' => true,
            'passes' => true,
        ];
    }

    /**
     * @dataProvider data
     */
    public function test_validation(array $data, ?\Closure $userCreate, bool $authorize, bool $passes): void
    {
        $request = FilesListFilterRequest::create('/');
        $request->setUserResolver(fn() => $userCreate());

        $validator = Validator::make($data, $request->rules());
        $this->assertEquals($authorize, $request->authorize());
        $this->assertEquals($passes, $validator->passes());

        $this->assertEquals($data['search'] ?? [], $validator->validated()['search'] ?? []);
    }
}
