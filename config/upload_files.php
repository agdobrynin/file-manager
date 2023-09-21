<?php
declare(strict_types=1);

use App\Helpers\PhpConfig;

return [
    'upload' => [
        'max_files' => PhpConfig::maxUploadFiles() ?: 10,
        'max_bytes' => PhpConfig::maxUploadFileBytes() ?: 262144,
        'max_post_bytes' => PhpConfig::maxPostBytes() ?: 262144,
    ],
    'move_to_cloud' => [
        'decay_minutes' => 1,
        'max_attempts' => 5,
    ],
];
