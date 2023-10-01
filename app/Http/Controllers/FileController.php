<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\UploadTreeFilesServiceInterface;
use App\Dto\FavoriteIdDto;
use App\Dto\FilesIdDto;
use App\Dto\FilesListFilterDto;
use App\Http\Requests\FavoriteRequest;
use App\Http\Requests\FilesActionRequest;
use App\Http\Requests\FilesListRequest;
use App\Http\Requests\FileUploadRequest;
use App\Http\Requests\StoreFolderRequest;
use App\Http\Resources\FileAncestorsResource;
use App\Http\Resources\FileResource;
use App\Jobs\MoveFileToCloud;
use App\Models\File;
use App\Models\FileFavorite;
use App\Services\MakeDownloadFiles;
use App\VO\FileFavoriteVO;
use App\VO\FileFolderVO;
use App\VO\UploadFilesVO;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class FileController extends Controller
{
    public function index(FilesListRequest $request): Response
    {
        $parentFolder = $request->parentFolder ?: File::rootFolderByUser($request->user());
        $this->authorize('view', $parentFolder);

        $dto = new FilesListFilterDto(...$request->validated());

        /** @var Builder $query */
        $query = File::filesList($request->user(), $dto, $parentFolder);

        $files = $query->paginate(config('app.my_files.per_page'))
            ->withQueryString();

        $fileResourceCollection = FileResource::collection($files);
        $ancestors = FileAncestorsResource::collection([...$parentFolder->ancestors, $parentFolder]);

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
        $children = $this->children($dto, $parentFolder);

        $children->each(function (File $file) {
            $this->authorize('delete', $file);
            $file->deleteQuietly();
        });

        return to_route('file.index', ['parentFolder' => $parentFolder]);
    }

    /**
     * @throws Throwable
     */
    public function download(FilesActionRequest $request, MakeDownloadFiles $downloadFiles): BinaryFileResponse
    {
        $parentFolder = $request->parentFolder ?: File::rootFolderByUser($request->user());
        $dto = new FilesIdDto(...$request->validated());
        $files = $this->children($dto, $parentFolder);

        $downloadDto = $downloadFiles->handle($files);

        return \response()->download($downloadDto->storagePath, $downloadDto->fileName)->deleteFileAfterSend();
    }

    public function favorite(FavoriteRequest $request): RedirectResponse
    {
        $dto = new FavoriteIdDto(...$request->validated());

        $favorite = new FileFavoriteVO($dto->id, Auth::id());
        /** @var FileFavorite $favorite */
        $favorite = FileFavorite::firstOrCreate($favorite->toArray());

        if (false === $favorite->wasRecentlyCreated) {
            $favorite->delete();
        }

        // TODO add flash message and catch flash on front.
        return back();
    }

    private function children(FilesIdDto $dto, File $parentFolder): Collection
    {
        return $dto->all
            ? $parentFolder->children()->get()
            : File::query()->whereIn('id', $dto->ids)->get();
    }
}
