<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FileResource extends JsonResource
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
            'isFavorite' => !!$this->favorite,
            'name' => $this->name,
            'disk' => $this->disk,
            'path' => $this->path,
            'parentId' => $this->parent_id,
            'isFolder' => $this->is_folder,
            'mime' => $this->mime,
            'size' => $this->size,
            'owner' => $this->owner,
            'createdAt' => $this->created_at->diffForHumans(),
            'updatedAt' => $this->updated_at->diffForHumans(),
            'createdBy' => $this->created_by,
            'updatedBy' => $this->updated_by,
            'deletedAt' => $this->deleted_at?->diffForHumans(),
        ];
    }
}
