<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Response;
use Inertia\ResponseFactory;

class FileTrashController extends Controller
{
    public function index(Request $request): Response|ResponseFactory
    {
        return inertia('MyTrash', []);
    }

    public function destroy(Request $request): void
    {

    }
}
