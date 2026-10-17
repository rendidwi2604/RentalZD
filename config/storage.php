<?php
require_once __DIR__ . '/env.php';

function storage_request(string $method, string $path, ?string $body = null, string $type = 'application/json'): array
{
    $base = rtrim(getenv('SUPABASE_URL') ?: '', '/');
    $key = getenv('SUPABASE_SERVICE_ROLE_KEY') ?: '';
    if (!str_starts_with($base, 'https://') || $key === '') {
        throw new RuntimeException('Konfigurasi Supabase Storage belum diisi.');
    }
    $ch = curl_init($base . '/storage/v1/' . $path);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => $method, CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 10, CURLOPT_TIMEOUT => 25,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $key, 'apikey: ' . $key, 'Content-Type: ' . $type],
    ]);
    if ($body !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($response === false || $status < 200 || $status >= 300) {
        error_log('Supabase Storage request failed: HTTP ' . $status);
        throw new RuntimeException('Penyimpanan bukti pembayaran gagal diakses. Periksa konfigurasi Storage.');
    }
    return json_decode($response, true) ?: [];
}

function storage_object_path(string $filename): string
{
    $bucket = getenv('SUPABASE_STORAGE_BUCKET') ?: 'bukti-bayar';
    return rawurlencode($bucket) . '/' . rawurlencode(basename($filename));
}

function storage_upload(string $tmpFile, string $filename, string $mime): void
{
    storage_request('POST', 'object/' . storage_object_path($filename), file_get_contents($tmpFile), $mime);
}

function storage_signed_url(string $filename): string
{
    $result = storage_request('POST', 'object/sign/' . storage_object_path($filename), json_encode(['expiresIn' => 60]));
    $url = $result['signedURL'] ?? '';
    if (!str_starts_with($url, '/object/sign/')) throw new RuntimeException('URL bukti pembayaran tidak valid.');
    return rtrim(getenv('SUPABASE_URL'), '/') . '/storage/v1' . $url;
}
