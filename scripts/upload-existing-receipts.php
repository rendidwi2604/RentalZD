<?php
// Run once locally after configuring .env and creating the private Storage bucket.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/config/storage.php';
foreach (glob(dirname(__DIR__) . '/uploads/bukti/*') as $file) {
    if (!preg_match('/\.(jpg|jpeg|png|webp|pdf)$/i', $file)) continue;
    try {
        storage_upload($file, basename($file), (new finfo(FILEINFO_MIME_TYPE))->file($file));
        echo 'Uploaded: ' . basename($file) . PHP_EOL;
    } catch (RuntimeException $e) {
        fwrite(STDERR, basename($file) . ': ' . $e->getMessage() . PHP_EOL);
        exit(1);
    }
}
