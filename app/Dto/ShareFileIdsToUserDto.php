<?php
declare(strict_types=1);

namespace App\Dto;

readonly class ShareFileIdsToUserDto
{
    public function __construct(public string $email, public bool $all, public ?array $ids = null)
    {
    }
}
