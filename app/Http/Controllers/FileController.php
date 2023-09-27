<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\UploadTreeFilesServiceInterface;
use App\Dto\FilesIdDto;
use App\Dto\MyFilesFilterDto;
use App\Http\Requests\FilesActionRequest;
use App\Http\Requests\FileUploadRequest;
use App\Http\Requests\MyFilesRequest;
use App\Http\Requests\StoreFolderRequest;
use App\Http\Resources\FileResource;
use App\Jobs\MoveFileToCloud;
use App\Models\File;
use App\Services\MakeDownloadFiles;
use App\VO\FileFolderVO;
use App\VO\UploadFilesVO;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class FileController extends Controller
{
    public function index(MyFilesRequest $request): Response
    {
        $parentFolder = $request->parentFolder ?: File::rootFolderByUser($request->user());
        $this->authorize('view', $parentFolder);

        $dto = new MyFilesFilterDto(...$request->validated());

        /** @var Builder $query */
        $query = File::myFiles($request->user(), $dto, $parentFolder);

        $files = $query->paginate(config('app.my_files.per_page'))
            ->withQueryString();

        $fileResourceCollection = FileResource::collection($files);
        $ancestors = FileResource::collection([...$parentFolder->ancestors, $parentFolder]);

        return inertia(
            'MyFiles', [
            'parentId' => $parentFolder->id,
            'files' => $fileResourceCollection,
            'ancestors' => $ancestors,
        ]);
    }

    public function create(StoreFolderRequest $request): RedirectResponse
    {
        $parentFolder = $request->parentFolder ?: File::rootFolderByUser($request->user());
        $this->authorize('create', $parentFolder);
        $newFolder = new FileFolderVO(...$request->validated());
        /** @var File $file */
        $file = File::query()->make($newFolder->toArray());

        $file->appendToNode($parentFolder)->save();

        return to_route('file.index', ['parentFolder' => $parentFolder]);
    }

    /**
     * @throws Throwable
     */
    public function upload(FileUploadRequest $request, UploadTreeFilesServiceInterface $filesService): RedirectResponse
    {
        $parentFolder = $request->parentFolder ?: File::rootFolderByUser($request->user());
        $this->authorize('create', $parentFolder);

        $vo = new UploadFilesVO(...$request->validated());
        $files = $filesService->upload($parentFolder, $vo->tree);

        foreach ($files as $file) {
            MoveFileToCloud::dispatch($file);
        }

        return to_route('file.index', ['parentFolder' => $parentFolder]);
    }

    public function destroy(FilesActionRequest $request): RedirectResponse
    {
        $parentFolder = $request->parentFolder ?: File::rootFolderByUser($request->user());

        $dto = new FilesIdDto(...$request->validated());

        /** @var Collection $children */
        $children = $dto->all
            ? $parentFolder->children()->get()
            : File::query()->whereIn('id', $dto->ids)->get();

        $children->each(function (File $file) {
            $this->authorize('delete', $file);
            $file->delete();
        });

        return to_route('file.index', ['parentFolder' => $parentFolder]);
    }

    /**
     * @throws Throwable
     */
    public function download(FilesActionRequest $request, MakeDownloadFiles $downloadFiles): BinaryFileResponse
    {
        $dto = new FilesIdDto(...$request->validated());

        $files = $dto->all
            ? $request->parentFolder->children()->get()
            : File::query()->whereIn('id', $dto->ids)->get();

        $dto = $downloadFiles->handle($files);

        return \response()->download($dto->path, $dto->fileName)
            ->deleteFileAfterSend();
    }
}
