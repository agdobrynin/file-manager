<?php

declare(strict_types=1);

namespace App\Helpers;

class Str
{
    public static function toByte(string $value): ?int
    {
        preg_match('/^(?<from>\d+)(?<scale>[a-zA-Z]+)?$/', mb_strtolower($value), $matches);

        $from = $matches['from'] ?? null;
        $scale = $matches['scale'] ?? null;

        return match ($scale) {
            'k', 'kb' => $from * 1024,
            'm', 'mb' => $from * (1024 ** 2),
            'g', 'gb' => $from * (1024 ** 3),
            default => $from ? (int) $from : null,
        };
    }

    public static function bytesForHumans(int $bytes): string
    {
        $base = log($bytes) / log(1024);
        if ($base > 0) {
            $units = [' bytes', ' kB', ' MB', ' GB', ' TB'];

            return round(1024 ** ($base - floor($base)), 2).$units[floor($base)];
        }

        return '';
    }
}
