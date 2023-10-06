<?php

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
            'name' => $this->name,
            'disk' => $this->disk,
            'path' => $this->path,
            'parentId' => $this->parent_id,
            'isFolder' => $this->is_folder,
            'mime' => $this->mime,
            'size' => $this->size,
            'owner' => $this->owner,
            'shareForUser' => FileShareForUserResource::collection($this->whenLoaded('fileShare')),
        ];
    }
}
