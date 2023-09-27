<?php

namespace App\Dto;

readonly class FilesListFilterDto
{
    public function __construct(public ?string $search = null)
    {
    }
}
