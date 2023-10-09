<?php

namespace Database\Factories;

use App\Enums\DiskEnum;
use App\VO\FileVO;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\File>
 */
class FileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $file = fake()->filePath() . '.' . fake()->fileExtension();

        return (new FileVO(
            name: $file,
            mime: 'image/jpg',
            size: random_int(1, 100),
            disk: DiskEnum::LOCAL,
            path: '/' . $file,
            storagePath: '/files/1/' . $file,
        ))->toArray();
    }
}
