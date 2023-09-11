<?php
declare(strict_types=1);

use App\Helpers\PhpConfig;

return [
    'upload' => [
        'max_files' => PhpConfig::maxUploadFiles(),
        'max_bytes' => PhpConfig::maxUploadFileBytes(),
        'max_post_bytes' => PhpConfig::maxPostBytes(),
    ]
];
