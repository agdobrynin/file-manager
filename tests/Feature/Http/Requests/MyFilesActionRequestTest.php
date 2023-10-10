<?php

namespace Tests\Feature\Http\Requests;

use App\Http\Requests\MyFilesActionRequest;
use App\Models\File;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class MyFilesActionRequestTest extends TestCase
{
    use RefreshDatabase;

    public static function data(): \Generator
    {
        yield 'success' => [
            'allKey' => false,
            'rootId' => 1,
            'errorKeys' => [],
        ];

        yield 'fail' => [
            'allKeys' => true,
            'rootId' => null,
            'errorKeys' => ['all']
        ];
    }

    /**
     * @dataProvider data
     */
    public function test_validation(bool $allKey, ?int $rootId, array $errorKeys): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $ids = [];

        if ($rootId) {
            $root = File::factory(state: ['id' => $rootId])->make()->makeRoot();
            $root->save();
            /** @var Collection $files */
            $files = File::factory(2, state: ['is_folder' => false])
                ->make()
                ->each(fn(File $file) => $root->appendNode($file));
            $ids = $files->pluck('id')->toArray();
        }

        $request = MyFilesActionRequest::create(
            '/file/destroy/' . $rootId,
            'DELETE',
            parameters: ['all' => $allKey, 'ids' => $ids]
        );
        $request->setUserResolver(fn() => $user);
        $request->setRouteResolver(fn() => Route::getRoutes()->match($request));

        $validator = Validator::make(['all' => $allKey, 'ids' => $ids], $request->rules());
        $validator->after($request->after());

        $request->authorize();
        $validator->passes();

        $this->assertCount(($rootId ? 2 : 0), $request->requestFiles);
        $this->assertEquals($errorKeys, $validator->errors()->keys());
    }
}
