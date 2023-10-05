<?php

namespace App\Http\Controllers;

use App\Dto\FilesListFilterDto;
use App\Http\Requests\FileShareListRequest;
use App\Http\Resources\FileShareResource;
use App\Models\File;
use Inertia\Response;
use Inertia\ResponseFactory;

class SharedForMeController extends Controller
{
    public function index(FileShareListRequest $request): Response|ResponseFactory
    {
        $dto = new FilesListFilterDto(...$request->validated());
        $query = File::fileShareForUser($request->user(), $dto);

        $files = $query->paginate(config('app.shared_for_me.per_page'))
            ->withQueryString();
        $fileResourceCollection = FileShareResource::collection($files);

        return inertia('SharedForMe', ['files' => $fileResourceCollection]);
    }
}
