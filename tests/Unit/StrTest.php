<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Helpers\Str;
use PHPUnit\Framework\TestCase;

class StrTest extends TestCase
{
    public static function stringBytes(): \Generator
    {
        yield '#1' => [
            'input' => '200',
            'equal' => 200,
        ];

        yield '#2' => [
            'input' => '100k',
            'equal' => 100 * 1024,
        ];

        yield '#3' => [
            'input' => '100kb',
            'equal' => 100 * 1024,
        ];

        yield '#4' => [
            'input' => '5M',
            'equal' => 5 * (1024 ** 2),
        ];

        yield '#5' => [
            'input' => '5Mb',
            'equal' => 5 * (1024 ** 2),
        ];

        yield '#6' => [
            'input' => '5 Mb',
            'equal' => null,
        ];

        yield '#7' => [
            'input' => '',
            'equal' => null,
        ];

        yield '#8' => [
            'input' => 'fiveMb',
            'equal' => null,
        ];

        yield '#9' => [
            'input' => '1G',
            'equal' => 1024 ** 3,
        ];

        yield '#10' => [
            'input' => '2Gb',
            'equal' => 2 * (1024 ** 3),
        ];
    }

    /**
     * @dataProvider stringBytes
     */
    public function test_to_byte(string $input, ?int $equal): void
    {
        $this->assertSame($equal, Str::toByte($input));
    }

    public static function bytesForHuman(): \Generator
    {
        yield '#1' => [
            'input' => 1_000,
            'equal' => '1000 bytes',
        ];

        yield '#2' => [
            'input' => 0,
            'equal' => '',
        ];

        yield '#3' => [
            'input' => 5_000,
            'equal' => '4.88 kB',
        ];

        yield '#4' => [
            'input' => 50_000,
            'equal' => '48.83 kB',
        ];

        yield '#5' => [
            'input' => 150_000_000,
            'equal' => '143.05 MB',
        ];
    }

    /**
     * @dataProvider bytesForHuman
     */
    public function test_bytes_for_humans(int $input, string $equal): void
    {
        $this->assertEquals($equal, Str::bytesForHumans($input));
    }
}
