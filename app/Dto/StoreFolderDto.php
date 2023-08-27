<?php

namespace App\Dto;

readonly class StoreFolderDto
{
    public function __construct(
        public string $name,
        public int    $parent_id,
    )
    {
    }
}
