<?php
declare(strict_types=1);

namespace App\Enums;

enum FlashMessagesEnum: string
{
    case INFO = 'info';
    case SUCCESS = 'success';
    case ERROR = 'error';
}
