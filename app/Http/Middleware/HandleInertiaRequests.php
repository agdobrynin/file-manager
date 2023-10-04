<?php

namespace App\Http\Middleware;

use App\Enums\FlashMessagesEnum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Middleware;
use Tightenco\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): string|null
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [
            'max_files' => $maxUploadFiles,
            'max_bytes' => $maxUploadFileBytes,
            'max_post_bytes' => $maxPostBytes,
        ] = config('upload_files.upload');

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $request->user(),
            ],
            'ziggy' => function () use ($request) {
                return array_merge((new Ziggy)->toArray(), [
                    'location' => $request->url(),
                ]);
            },
            'upload' => [
                'maxUploadFiles' => $maxUploadFiles ?: 100,
                'maxUploadFileBytes' => $maxUploadFileBytes,
                'maxPostBytes' => $maxPostBytes,
            ],
            'flash' => [
                FlashMessagesEnum::INFO->value => fn () => $request->session()->get(FlashMessagesEnum::INFO->value),
                FlashMessagesEnum::SUCCESS->value => fn () => $request->session()->get(FlashMessagesEnum::SUCCESS->value),
                FlashMessagesEnum::ERROR->value => fn () => $request->session()->get(FlashMessagesEnum::ERROR->value),
                FlashMessagesEnum::WARNING->value => fn () => $request->session()->get(FlashMessagesEnum::WARNING->value),
            ],
            'route_name' => Route::current()?->getName(),
        ]);
    }
}
