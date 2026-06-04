<?php

declare(strict_types=1);

return [
    /*
    | The filesystem disk used for the durable audit archive. Any S3-compatible disk
    | works (AWS S3 or Cloudflare R2) since both use Laravel's "s3" driver. Defaults to
    | the app's primary filesystem disk.
    */
    'archive_disk' => env('AUDIT_ARCHIVE_DISK', env('FILESYSTEM_DISK', 's3')),

    /*
    | Base path (prefix) under which audit log files are written on the archive disk.
    */
    'archive_path' => env('AUDIT_ARCHIVE_PATH', 'audit-logs'),

    /*
    | Queue connection/queue used for asynchronous archiving of pointwise events.
    */
    'queue' => env('AUDIT_QUEUE', 'default'),
];
