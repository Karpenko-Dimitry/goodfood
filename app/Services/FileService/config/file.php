<?php

return [
    'filesystem_driver' => env('FILE_STORAGE_DRIVER', 'public'),
    'storage_path' => env('FILE_STORAGE_PATH', 'assets/files'),

    'tmp_filesystem_disk' => env('FILE_TMP_STORAGE_DISK', 'temp'),
    'tmp_storage_path' => env('FILE_TMP_STORAGE_PATH', '/'),

    'use_queue' => env('FILE_CONVERT_IN_QUEUE', false),
    'queue_name' => env('FILE_QUEUE_NAME', 'media'),
    'defer_uploading' => env('FILE_DEFER_UPLOADING', false),
    'calc_dominant_color' => env('FILE_CALC_DOMINANT_COLOR', false),
];
