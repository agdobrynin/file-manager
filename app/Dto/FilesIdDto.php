<?php
declare(strict_types=1);

namespace App\Dto;

readonly class FilesIdDto
{
    public function __construct(public bool $all, public ?array $ids = null)
    {
    }
}
