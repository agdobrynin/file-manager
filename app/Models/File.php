<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Kalnoy\Nestedset\NodeTrait;

class File extends Model
{
    use HasFactory;
    use NodeTrait;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'path',
        'is_folder',
        'mime',
        'size',
    ];
    protected $casts = [
        'is_folder' => 'boolean'
    ];

    public static function boot(): void
    {
        parent::boot();

        static::creating(static function (File $model) {
            $model->created_by = Auth::id();
            $model->updated_by = Auth::id();
        });

        static::updating(static function (File $model) {
            $model->updated_by = Auth::id();
        });
    }
}
