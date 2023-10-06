<?php
declare(strict_types=1);

namespace App\Dto;

readonly class MyFilesListFilterDto
{
    public function __construct(public ?string $search = null, public ?bool $onlyFavorites = null)
    {
    }
}
