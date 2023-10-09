<?php

namespace Http\Requests;

use App\Http\Requests\FilesActionRequest;
use App\Models\File;
use App\Models\User;
use Closure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class FilesActionRequestTest extends TestCase
{
    use RefreshDatabase;

    public static function dataValidation(): \Generator
    {
        yield 'empty' => [
            'data' => [],
            'authorize' => true,
            'fails' => true,
            'passes' => false,
            'error keys' => ['all'],
        ];

        yield 'ids must be not null if all is false' => [
            'data' => ['all' => '0'],
            'authorize' => true,
            'fails' => true,
            'passes' => false,
            'error keys' => ['ids'],
        ];

        yield 'ids min:1 if all is false' => [
            'data' => ['all' => '0', 'ids' => []],
            'authorize' => true,
            'fails' => true,
            'passes' => false,
            'error keys' => ['ids'],
        ];

        yield 'success with ids' => [
            'data' => function () {
                $ids = File::factory(2)->create()->pluck('id')->toArray();

                return ['all' => '0', 'ids' => $ids];
            },
            'authorize' => true,
            'fails' => false,
            'passes' => true,
            'error keys' => [],
        ];

        yield 'success with all' => [
            'data' => ['all' => '1'],
            'authorize' => true,
            'fails' => false,
            'passes' => true,
            'error keys' => [],
        ];
    }

    /**
     * @dataProvider dataValidation
     */
    public function test_validation(array|Closure $data, bool $authorize, bool $fails, bool $passes, array $errorKeys = []): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $request = FilesActionRequest::create('/', 'POST');
        $request->setUserResolver(fn() => $user);

        if ($data instanceof Closure) {
            $data = $data();
        }

        $validator = Validator::make($data, $request->rules());

        $this->assertEquals($authorize, $request->authorize());
        $this->assertEquals($fails, $validator->fails());
        $this->assertEquals($passes, $validator->passes());

        if ($validator->passes() && count($data['ids'] ?? [])) {
            $this->assertCount(count($data['ids']), $request->requestFiles);
        }

        if ($errorKeys) {
            $this->assertEquals($errorKeys, $validator->errors()->keys());
        }
    }
}
