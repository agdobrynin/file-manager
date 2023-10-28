<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FileShareResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->file->name,
            'disk' => $this->file->disk,
            'path' => $this->file->path,
            'parentId' => $this->file->parent_id,
            'isFolder' => $this->file->is_folder,
            'mime' => $this->file->mime,
            'size' => $this->file->size,
            'owner' => $this->file->owner,
            'updatedAt' => $this->updated_at->diffForHumans(),
            'shareForUser' => $this->forUser->name,
        ];
    }
}
