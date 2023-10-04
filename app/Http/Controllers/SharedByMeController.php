<?php

namespace App\Http\Controllers;

use Inertia\Response;
use Inertia\ResponseFactory;

class SharedByMeController extends Controller
{
    public function index(): Response|ResponseFactory
    {
        return inertia('SharedByMe', ['files' => []]);
    }
}
