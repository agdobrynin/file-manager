<?php
declare(strict_types=1);

namespace App\Dto;

readonly class ShareFilesDto
{
    public function __construct(public string $email, public bool $all, public ?array $ids = null)
    {
    }
}
