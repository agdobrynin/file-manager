<?php

namespace Tests\Feature\Http\Requests;

use App\Http\Requests\ParentIdBaseRequest;
use App\Models\File;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ParentIdBaseRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_folder_not_found(): void
    {
        $this->expectException(ModelNotFoundException::class);
        $request = ParentIdBaseRequest::create('/file/1');
        $request->setRouteResolver(fn() => Route::getRoutes()->match($request));
        $request->authorize();
    }

    public function test_folder_as_file(): void
    {
        $this->actingAs(User::factory()->create());
        $file = File::factory()->create();

        $request = ParentIdBaseRequest::create('/file/' . $file->id);
        $request->setRouteResolver(fn() => Route::getRoutes()->match($request));
        $this->assertFalse($request->authorize());
    }

    public function test_folder_success(): void
    {
        $this->actingAs(User::factory()->create());
        $folder = File::factory(['is_folder' => true])->create();

        $request = ParentIdBaseRequest::create('/file/' . $folder->id);
        $request->setRouteResolver(fn() => Route::getRoutes()->match($request));
        $this->assertTrue($request->authorize());
        $this->assertEquals($folder->id, $request->parentFolder->id);
    }
}
