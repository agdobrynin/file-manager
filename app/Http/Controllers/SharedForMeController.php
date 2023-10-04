<?php

namespace App\Http\Controllers;

use Inertia\Response;
use Inertia\ResponseFactory;

class SharedForMeController extends Controller
{
    public function index(): Response|ResponseFactory
    {
        return inertia('SharedForMe', ['files' => []]);
    }
}
