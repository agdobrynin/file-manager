<?php
declare(strict_types=1);

namespace App\Dto;

readonly class FavoriteIdDto
{
    public function __construct(public int $id)
    {
    }
}
