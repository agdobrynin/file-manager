<?php

namespace App\VO;

use App\Contracts\ModelAttributeInterface;

readonly class FileFavoriteVO implements ModelAttributeInterface
{
    public function __construct(public int $fileId, public int $userId)
    {
    }

    public function toArray(): array
    {
        return [
            'file_id' => $this->fileId,
            'user_id' => $this->userId,
        ];
    }
}
