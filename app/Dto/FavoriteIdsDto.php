<?php

namespace App\Dto;

readonly class FavoriteIdsDto
{
    public function __construct(public array $ids)
    {
    }
}
