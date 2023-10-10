<?php

namespace Tests\Feature\Http\Requests;

use App\Http\Requests\FileShareActionRequest;
use App\Models\File;
use App\Models\FileShare;
use App\Models\User;
use Closure;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class FileShareActionRequestTest extends TestCase
{
    use RefreshDatabase;

    public static function dataValidation(): \Generator
    {
        yield 'empty' => [
            'data' => [],
            'authorize' => true,
            'fails' => true,
            'passes' => false,
            'errors' => ['all' => ['The all field is required.']],
        ];

        yield 'all true' => [
            'data' => ['all' => '1'],
            'authorize' => true,
            'fails' => false,
            'passes' => true,
            'errors' => [],
        ];

        yield 'all false and ids is empty' => [
            'data' => ['all' => false],
            'authorize' => true,
            'fails' => true,
            'passes' => false,
            'errors' => ['ids' => ['The ids field is required when all is false.']],
        ];

        yield 'all false and ids of file share not found' => [
            'data' => function (User $user) {
                Auth::shouldReceive('id')->andReturn($user->id);
                $ids = File::factory(2)->create()
                    ->pluck('id')->toArray();

                return ['all' => false, 'ids' => $ids];
            },
            'authorize' => true,
            'fails' => true,
            'passes' => false,
            'errors' => ['ids' => ['Some file share IDs are not valid.']],
        ];

        yield 'all false and ids of file share exist file owner' => [
            'data' => function (User $user) {
                Auth::shouldReceive('id')->andReturn($user->id);
                /** @var Collection $fileShares */
                $fileShares = FileShare::factory(2)
                    ->for(File::factory())
                    ->create(['for_user_id' => $user->id]);

                return ['all' => false, 'ids' => $fileShares->pluck('id')->toArray()];
            },
            'authorize' => true,
            'fails' => false,
            'passes' => true,
            'errors' => [],
        ];

        yield 'all false and ids of file share where owner is other user' => [
            'data' => function (User $user) {
                $otherUSer = User::factory()->create();
                Auth::shouldReceive('id')->andReturn($otherUSer->id);
                /** @var Collection $fileShares */
                $fileShares = FileShare::factory(2)
                    ->for(File::factory())
                    ->create(['for_user_id' => $user->id]);

                return ['all' => false, 'ids' => $fileShares->pluck('id')->toArray()];
            },
            'authorize' => true,
            'fails' => false,
            'passes' => true,
            'errors' => [],
        ];
    }

    /**
     * @dataProvider dataValidation
     */
    public function test_validation(array|Closure $data, bool $authorize, bool $fails, bool $passes, array $errors = []): void
    {
        $user = User::factory()->create();

        $request = FileShareActionRequest::create('/share');
        $request->setUserResolver(fn() => $user);

        if ($data instanceof Closure) {
            $data = $data($user);
        }

        $validator = Validator::make($data, $request->rules());

        $this->assertEquals($authorize, $request->authorize());
        $this->assertEquals($fails, $validator->fails());
        $this->assertEquals($passes, $validator->passes());
        $this->assertEquals($errors, $validator->errors()->getMessages());
    }
}
