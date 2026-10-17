<?php
require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/midtrans_config.php';
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
$payload = json_decode(file_get_contents('php://input'), true);
$order = $payload['order_id'] ?? '';
if (!preg_match('/^ZDR-(?:M|MOBIL|MOTOR)-[0-9]+-[0-9]+(?:-[a-f0-9]+)?$/', $order)) {
    http_response_code(400); exit(json_encode(['success'=>false]));
}
$expected = hash('sha512', $order . ($payload['status_code'] ?? '') . ($payload['gross_amount'] ?? '') . MIDTRANS_SERVER_KEY);
if (!hash_equals($expected, $payload['signature_key'] ?? '')) {
    http_response_code(403); exit(json_encode(['success'=>false]));
}
try {
    // Verify status directly with Midtrans before updating the rental.
    $status = \Midtrans\Transaction::status($order);
    $paid = $status->transaction_status === 'settlement' ||
        ($status->transaction_status === 'capture' && ($status->fraud_status ?? '') === 'accept');
    if ($paid && $status->order_id === $order) {
        // Existing order IDs use M for both mobil and motor; match the stored order ID.
        foreach (['log_penyewaan', 'log_sewa_motor'] as $table) {
            db_query($conn, "UPDATE {$table} SET payment_status='lunas' WHERE midtrans_order_id=? AND total_bayar=?", [$order, $status->gross_amount]);
        }
    }
    echo json_encode(['success'=>true]);
} catch (Throwable $e) {
    error_log('Midtrans notification verification failed (' . $e->getCode() . ').');
    http_response_code(503); echo json_encode(['success'=>false]);
}
