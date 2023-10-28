<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FileInTrashResource extends JsonResource
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
            'name' => $this->name,
            'mime' => $this->mime,
            'disk' => $this->disk,
            'path' => $this->path,
            'parentId' => $this->parent_id,
            'isFolder' => $this->is_folder,
            'size' => $this->size,
            'deletedAt' => $this->deleted_at?->diffForHumans(),
        ];
    }
}
