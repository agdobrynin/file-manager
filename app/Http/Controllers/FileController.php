<?php

namespace App\Http\Controllers;

use App\Dto\MyFilesFilterDto;
use App\Dto\StoreFolderDto;
use App\Http\Requests\MyFilesRequest;
use App\Http\Requests\StoreFolderRequest;
use App\Http\Resources\FileResource;
use App\Models\File;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

class FileController extends Controller
{
    public function myFiles(
        MyFilesRequest $request,
        ?File          $parentFolder = null,
    ): Response
    {
        $this->authorize('view', $request->parentFolder);

        $dto = new MyFilesFilterDto(...$request->validated());

        $files = File::myFiles($request->user(), $dto, $request->parentFolder)->get();

        $fileResourceCollection = FileResource::collection($files);
        $ancestors = FileResource::collection([...$request->parentFolder->ancestors, $request->parentFolder]);

        return inertia(
            'MyFiles', [
            'parentId' => $request->parentFolder->id,
            'files' => $fileResourceCollection,
            'ancestors' => $ancestors,
        ]);
    }

    public function createFolder(StoreFolderRequest $request, ?File $parentFolder = null): RedirectResponse
    {
        $this->authorize('createFolder', $request->parentFolder);

        $dto = new StoreFolderDto(...$request->validated());
        /** @var File $file */
        $file = File::make([
            'name' => $dto->name,
            'is_folder' => true,
        ]);

        $file->appendToNode($request->parentFolder)->save();

        return to_route('my.files', ['parentFolder' => $request->parentFolder]);
    }
}
