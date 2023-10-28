<?php

declare(strict_types=1);

namespace App\VO;

use App\Contracts\ModelAttributeInterface;
use App\Enums\DiskEnum;

readonly class FileFolderVO implements ModelAttributeInterface
{
    public function __construct(public string $name)
    {
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'is_folder' => true,
            'disk' => DiskEnum::LOCAL,
        ];
    }
}
