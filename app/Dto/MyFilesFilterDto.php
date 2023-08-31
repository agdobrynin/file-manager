<?php

namespace App\Dto;

readonly class MyFilesFilterDto
{
    public function __construct(public ?string $search = null)
    {
    }
}
