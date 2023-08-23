<?php

namespace App\Http\Controllers;

use Inertia\Response;
use Inertia\ResponseFactory;

class FileController extends Controller
{
    public function myFiles(): Response|ResponseFactory
    {
        return inertia('MyFiles');
    }
}
