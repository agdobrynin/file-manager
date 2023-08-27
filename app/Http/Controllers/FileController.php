<?php

namespace App\Http\Controllers;

use App\Dto\StoreFolderDto;
use App\Http\Requests\StoreFolderRequest;
use App\Models\File;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;
use Inertia\ResponseFactory;

class FileController extends Controller
{
    public function myFiles(Request $request, ?string $folder = null): Response|ResponseFactory
    {
        return inertia('MyFiles', ['folder' => $folder]);
    }

    public function createFolder(StoreFolderRequest $request): RedirectResponse
    {
        $dto = new StoreFolderDto(...$request->validated());

        $request->parent->appendNode(File::make([
            'name' => $dto->name,
        ]));

        $pathToFolder = $request->parent->ancestors->each(fn(File $node) => $node->name);

        return to_route('my.files', ['folder' => $pathToFolder]);
    }
}
