<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Support\Facades\Auth;

trait HasCreatorAndUpdater
{
    protected static function bootHasCreatorAndUpdater(): void
    {
        static::creating(static function (self $model): void {
            $model->created_by = Auth::id();
            $model->updated_by = Auth::id();
        });

        static::updating(static function (self $model): void {
            $model->updated_by = Auth::id();
        });
    }
}
