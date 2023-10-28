<?php

declare(strict_types=1);

namespace App\Dto;

readonly class ErrorMessageDto
{
    public function __construct(public string $message, public array $errors = [])
    {
    }
}
