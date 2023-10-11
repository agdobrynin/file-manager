<?php

namespace Database\Factories;

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
        ];
    }

    public function isFile(): FileFactory|Factory
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
            name: $this->faker->title,
        );

        return $this->state($vo->toArray());
    }
}
