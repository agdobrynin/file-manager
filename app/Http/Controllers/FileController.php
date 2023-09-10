<?php

namespace App\Http\Controllers;

use App\Contracts\UploadTreeFilesServiceInterface;
use App\Dto\MyFilesFilterDto;
use App\Http\Requests\FileUploadRequest;
use App\Http\Requests\MyFilesRequest;
use App\Http\Requests\StoreFolderRequest;
use App\Http\Resources\FileResource;
use App\Jobs\MoveFileToCloud;
use App\Models\File;
use App\VO\FileFolderVO;
use App\VO\UploadFilesVO;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;
use Throwable;

class FileController extends Controller
{
    public function myFiles(MyFilesRequest $request): Response
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

    public function createFolder(StoreFolderRequest $request): RedirectResponse
    {
        $this->authorize('create', $request->parentFolder);
        $newFolder = new FileFolderVO(...$request->validated());
        /** @var File $file */
        $file = File::query()->make($newFolder->toArray());

        $file->appendToNode($request->parentFolder)->save();

        return to_route('my.files', ['parentFolder' => $request->parentFolder]);
    }

    /**
     * @throws Throwable
     */
    public function upload(FileUploadRequest $request, UploadTreeFilesServiceInterface $filesService): RedirectResponse
    {
        $this->authorize('create', $request->parentFolder);

        $vo = new UploadFilesVO(...$request->validated());
        $files = $filesService->upload($request->parentFolder, $vo->tree);

        foreach ($files as $file) {
            MoveFileToCloud::dispatch($file);
        }

        return to_route('my.files', ['parentFolder' => $request->parentFolder]);
    }
}
