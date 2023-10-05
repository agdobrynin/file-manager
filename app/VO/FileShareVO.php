<?php
declare(strict_types=1);

namespace App\VO;

use App\Contracts\ModelAttributeInterface;
use App\Models\File;
use App\Models\User;

readonly class FileShareVO implements ModelAttributeInterface
{
    public function __construct(public User $user, public File $file)
    {
    }

    public function toArray(): array
    {
        $date = new \DateTimeImmutable();

        return [
            'file_id' => $this->file->id,
            'for_user_id' => $this->user->id,
            'created_at' => $date,
            'updated_at' => $date,
        ];
    }
}