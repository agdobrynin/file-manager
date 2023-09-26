<?php
declare(strict_types=1);

namespace App\Enums;

enum DiskEnum: string
{
    case CLOUD = 'cloud';
    case LOCAL = 'local';
}
