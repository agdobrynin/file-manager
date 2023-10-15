<?php

namespace Database\Factories;

use App\Enums\DiskEnum;
use App\VO\FileFolderVO;
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
        return [
            'name' => fake()->name,
            'is_folder' => false,
            'path' => null,
            'disk' => DiskEnum::LOCAL,
        ];
    }

    public function isFile(): Factory
    {
        $name = $this->faker->title;

        $vo = new FileVO(
            name: $name,
            mime: $this->faker->mimeType(),
            size: 100,
            path: $name,
            storagePath: 'file/' . $name,
        );

        return $this->state($vo->toArray());
    }

    public function isFolder(): FileFactory|Factory
    {
        $vo = new FileFolderVO(
            name: $this->faker->name,
        );

        return $this->state($vo->toArray());
    }
}
