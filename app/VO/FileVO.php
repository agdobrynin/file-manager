<?php
declare(strict_types=1);

namespace App\VO;

use App\Contracts\ModelAttributeInterface;
use App\Enums\DiskEnum;

readonly class FileVO implements ModelAttributeInterface
{

    public function __construct(
        public string   $name,
        public string   $mime,
        public int      $size,
        public ?string  $path,
        public ?string  $storagePath,
        public DiskEnum $disk = DiskEnum::LOCAL,
    )
    {
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'is_folder' => false,
            'mime' => $this->mime,
            'size' => $this->size,
            'disk' => $this->disk,
            'path' => $this->path,
            'storage_path' => $this->storagePath,
        ];
    }
}
