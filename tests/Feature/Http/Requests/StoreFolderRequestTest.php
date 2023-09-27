<?php

namespace Tests\Feature\Http\Requests;

use App\Enums\DiskEnum;
use App\Http\Requests\StoreFolderRequest;
use App\Models\File;
use App\Models\User;
use App\VO\FileFolderVO;
use App\VO\FileVO;
use Generator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreFolderRequestTest extends TestCase
{
    use RefreshDatabase;

    public static function data(): Generator
    {
        yield 'folder name with invalid symbols #1' => [
            'data' => ['name' => 'Folder<folder>'],
            'passed' => false,
            'errors key' => ['name']
        ];

        yield 'folder name with invalid symbols #2' => [
            'data' => ['name' => 'Folder * folder'],
            'passed' => false,
            'errors key' => ['name']
        ];

        yield 'folder name with invalid symbols #3' => [
            'data' => ['name' => 'Folder / folder'],
            'passed' => false,
            'errors key' => ['name']
        ];

        yield 'folder name with invalid symbols #4' => [
            'data' => ['name' => 'Folder | folder'],
            'passed' => false,
            'errors key' => ['name']
        ];

        yield 'folder name with invalid symbols #5' => [
            'data' => ['name' => 'Folder | folder'],
            'passed' => false,
            'errors key' => ['name']
        ];

        yield 'folder name with invalid symbols #6' => [
            'data' => ['name' => 'Folder: folder'],
            'passed' => false,
            'errors key' => ['name']
        ];

        yield 'folder name with invalid symbols #7' => [
            'data' => ['name' => 'Folder? folder'],
            'passed' => false,
            'errors key' => ['name']
        ];

        yield 'folder name with invalid symbols #8' => [
            'data' => ['name' => 'Folder\folder'],
            'passed' => false,
            'errors key' => ['name']
        ];

        yield 'folder success' => [
            'data' => ['name' => '🎈 новая папка'],
            'passed' => true,
            'errors key' => []
        ];

        yield 'folder is required' => [
            'data' => [],
            'passed' => false,
            'errors key' => ['name']
        ];

        yield 'folder is required with empty string' => [
            'data' => ['name' => '   '],
            'passed' => false,
            'errors key' => ['name']
        ];
    }

    /**
     * @dataProvider data
     */
    public function test_validation(array $data, bool $passes, array $errorKeys): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $root = File::makeRootByUser($user);

        $request = StoreFolderRequest::create('/file/create/' . $root->id, 'POST');
        $request->setRouteResolver(fn() => Route::getRoutes()->match($request));

        $this->assertTrue($request->authorize());
        $this->assertEquals($root->id, $request->parentFolder->id);


        $validator = Validator::make($data, $request->rules());
        $this->assertEquals($passes, $validator->passes());

        $this->assertEquals($errorKeys, $validator->errors()->keys());
    }

    public function test_validation_unique(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $root = File::makeRootByUser($user);
        $folder = File::make((new FileFolderVO(name: 'Папка 1'))->toArray());
        $root->appendNode($folder);

        $request = StoreFolderRequest::create('/file/create/' . $root->id, 'POST');
        $request->setRouteResolver(fn() => Route::getRoutes()->match($request));
        $request->authorize();

        $this->assertEquals($root->id, $request->parentFolder->id);

        $validator = Validator::make(['name' => 'Папка 1'], $request->rules());
        $this->assertFalse($validator->passes());
    }

    public function test_create_folder_on_file(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $root = File::makeRootByUser($user);
        $fileVO = new FileVO(
            name: 'Папка 1',
            mime: 'image/jpg',
            size: 1000,
            disk: DiskEnum::LOCAL,
            path: 'path',
        );
        $file = File::make($fileVO->toArray());
        $root->appendNode($file);

        $request = StoreFolderRequest::create('/file/create/' . $file->id, 'POST');
        $request->setRouteResolver(fn() => Route::getRoutes()->match($request));

        $this->assertFalse($request->authorize());
    }

    public function test_folder_not_found(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $request = StoreFolderRequest::create('/file/create/10000000000', 'POST');
        $request->setRouteResolver(fn() => Route::getRoutes()->match($request));

        $this->expectException(ModelNotFoundException::class);

        $this->assertFalse($request->authorize());
    }

    public function test_empty_parameter_parent_folder(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        File::makeRootByUser($user);

        $request = StoreFolderRequest::create('/file/create', 'POST', ['name' => 'abc']);
        $request->setRouteResolver(fn() => Route::getRoutes()->match($request));

        $this->assertTrue($request->authorize());
        $this->assertNull($request->parentFolder);

        $validator = Validator::make($request->validationData(), $request->rules());

        $this->assertTrue($validator->passes());
    }
}
