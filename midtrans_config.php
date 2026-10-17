<?php
require_once __DIR__ . '/config/env.php';
define('MIDTRANS_IS_PRODUCTION', filter_var(getenv('MIDTRANS_IS_PRODUCTION') ?: 'false', FILTER_VALIDATE_BOOLEAN));
define('MIDTRANS_SERVER_KEY', getenv('MIDTRANS_SERVER_KEY') ?: '');
define('MIDTRANS_CLIENT_KEY', getenv('MIDTRANS_CLIENT_KEY') ?: '');
if (MIDTRANS_SERVER_KEY === '' || MIDTRANS_CLIENT_KEY === '') {
    http_response_code(503); exit('Pembayaran Midtrans belum dikonfigurasi.');
}
require_once __DIR__ . '/vendor/autoload.php';
\Midtrans\Config::$serverKey = MIDTRANS_SERVER_KEY;
\Midtrans\Config::$isProduction = MIDTRANS_IS_PRODUCTION;
\Midtrans\Config::$isSanitized = true;
\Midtrans\Config::$is3ds = true;
\Midtrans\Config::$curlOptions[CURLOPT_CONNECTTIMEOUT] = 10;
\Midtrans\Config::$curlOptions[CURLOPT_TIMEOUT] = 25;
define('MIDTRANS_NOTIFICATION_URL', rtrim(getenv('APP_URL') ?: '', '/') . '/midtrans_notification.php');
