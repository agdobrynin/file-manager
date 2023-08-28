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

    public function createFolder(StoreFolderRequest $request): void
    {
        if (!$request->parent) {
            abort(404, 'Parent folder not found');
        }

        $dto = new StoreFolderDto(...$request->validated());
        /** @var File $file */
        $file = File::make([
            'name' => $dto->name,
            'is_folder' => true,
        ]);

        $file->appendToNode($request->parent)->save();
    }
}
