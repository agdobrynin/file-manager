<?php

namespace Tests\Feature\Http\Requests;

use App\Http\Requests\FilesActionTrashRequest;
use App\Models\File;
use App\Models\User;
use Closure;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class FilesActionTrashRequestTest extends TestCase
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

        yield 'all true' => [
            'data' => ['all' => '1'],
            'authorize' => true,
            'fails' => false,
            'passes' => true,
            'error keys' => [],
        ];

        yield 'all false and ids is empty' => [
            'data' => ['all' => false],
            'authorize' => true,
            'fails' => true,
            'passes' => false,
            'error keys' => ['ids'],
        ];

        yield 'all false and ids with files not in trash' => [
            'data' => function () {
                $ids = File::factory(2)->create()->pluck('id')->toArray();

                return ['all' => false, 'ids' => $ids];
            },
            'authorize' => true,
            'fails' => true,
            'passes' => false,
            'error keys' => ['ids'],
        ];

        yield 'all false and ids in trash' => [
            'data' => function () {
                /** @var Collection $files */
                $files = File::factory(2)->create();
                File::destroy($files);
                $ids = $files->pluck('id')->toArray();

                return ['all' => false, 'ids' => $ids];
            },
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

        $request = FilesActionTrashRequest::create('/trash');
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

        $this->assertEquals($errorKeys, $validator->errors()->keys());
    }
}
