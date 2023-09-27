<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Dto\FilesListFilterDto;
use App\Http\Requests\FilesListRequest;
use App\Http\Resources\FileResource;
use App\Models\File;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Response;
use Inertia\ResponseFactory;

class FileTrashController extends Controller
{
    public function index(FilesListRequest $request): Response|ResponseFactory
    {
        $dto = new FilesListFilterDto(...$request->validated());

        /** @var Builder $query */
        $query = File::filesInTrash($request->user(), $dto);

        $files = $query->paginate(config('app.my_files.per_page'))
            ->withQueryString();

        $fileResourceCollection = FileResource::collection($files);

        return inertia('MyTrash', [
            'files' => $fileResourceCollection,
        ]);
    }

    public function destroy(Request $request): void
    {

    }
}
