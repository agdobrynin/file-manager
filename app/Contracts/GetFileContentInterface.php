<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\File;
use App\Services\Exceptions\FileContentNotFoundException;

interface GetFileContentInterface
{
    /**
     * @throws FileContentNotFoundException
     */
    public function getContent(File $file): ?string;
}
