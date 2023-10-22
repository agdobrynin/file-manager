<?php

namespace Database\Factories;

use App\Enums\DiskEnum;
use App\Models\User;
use App\VO\FileFolderVO;
use App\VO\FileVO;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

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
        $name = Str::random() . '.' . $this->faker->fileExtension();

        return [
            'name' => $name,
            'is_folder' => false,
            'path' => null,
            'disk' => DiskEnum::LOCAL,
            'size' => fake()->numberBetween(10, 200),
            'storage_path' => '/files/' . $name
        ];
    }

    public function forUser(User $user): Factory
    {
        return $this->for($user, 'user')
            ->for($user, 'userUpdate');
    }

    public function isFile(?User $user = null): FileFactory|Factory
    {
        $name = Str::random() . '.' . $this->faker->fileExtension();

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

    public function deleted(): FileFactory|Factory
    {
        return $this->state(fn (array $attributes) => [
            'deleted_at' => now(),
        ]);
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
