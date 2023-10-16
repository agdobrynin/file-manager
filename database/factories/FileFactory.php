<?php

namespace Database\Factories;

use App\Enums\DiskEnum;
use App\Models\User;
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

    public function isFile(?User $user = null): Factory
    {
        $name = $this->faker->title;

        $vo = new FileVO(
            name: $name,
            mime: $this->faker->mimeType(),
            size: 100,
            path: $name,
            storagePath: 'file/' . $name,
        );

        $factory = $this->state($vo->toArray());

        if ($user) {
            $factory = $factory->for($user, 'user')
                ->for($user, 'userUpdate');
        }

        return $factory;
    }

    public function isFolder(?User $user = null): FileFactory|Factory
    {
        $vo = new FileFolderVO(
            name: $this->faker->name,
        );

        $factory = $this->state($vo->toArray());

        if ($user) {
            $factory = $factory->for($user, 'user')
                ->for($user, 'userUpdate');
        }

        return $factory;
    }
}
