<?php

namespace App\Http\Controllers;

use App\Dto\FilesListFilterDto;
use App\Http\Requests\FilesListFilterRequest;
use App\Http\Resources\FileShareResource;
use App\Models\FileShare;
use Inertia\Response;
use Inertia\ResponseFactory;

class SharedForMeController extends Controller
{
    public function index(FilesListFilterRequest $request): Response|ResponseFactory
    {
        $dto = new FilesListFilterDto(...$request->validated());
        $query = FileShare::fileShareForUser($request->user(), $dto);
        $files = $query->paginate(config('app.shared_for_me.per_page'))
            ->withQueryString();
        $fileResourceCollection = FileShareResource::collection($files);

        return inertia('SharedForMe', ['files' => $fileResourceCollection]);
    }
}
