<?php
require_once __DIR__ . '/env.php';

final class AppDatabase extends PDO
{
    public int $insertId = 0;
}

// Buffered results preserve row counts and rewind behavior used by the views.
final class AppResult
{
    public array $rows;
    public int $position = 0;
    public function __construct(array $rows) { $this->rows = $rows; }
}

function app_database(): AppDatabase
{
    static $db;
    if ($db instanceof AppDatabase) return $db;
    try {
        $url = getenv('DATABASE_URL');
        if ($url) {
            $parts = parse_url($url);
            if (!$parts || !in_array($parts['scheme'] ?? '', ['postgres', 'postgresql'], true)) {
                throw new RuntimeException('DATABASE_URL harus berupa URI PostgreSQL.');
            }
            $host = $parts['host'] ?? '';
            $port = $parts['port'] ?? 6543;
            $name = rawurldecode(ltrim($parts['path'] ?? '/postgres', '/'));
            $user = rawurldecode($parts['user'] ?? '');
            $pass = rawurldecode($parts['pass'] ?? '');
            parse_str($parts['query'] ?? '', $options);
            $ssl = $options['sslmode'] ?? (getenv('DB_SSLMODE') ?: 'require');
        } else {
            $host = getenv('DB_HOST') ?: '';
            $port = getenv('DB_PORT') ?: 6543;
            $name = getenv('DB_NAME') ?: 'postgres';
            $user = getenv('DB_USER') ?: '';
            $pass = getenv('DB_PASS') ?: '';
            $ssl = getenv('DB_SSLMODE') ?: 'require';
        }
        if ($host === '' || $user === '') throw new RuntimeException('Konfigurasi database belum diisi.');
        foreach ([$host, $name, $ssl] as $value) {
            if (preg_match('/[;\r\n]/', $value)) throw new RuntimeException('Konfigurasi database tidak valid.');
        }
        if (!ctype_digit((string)$port)) throw new RuntimeException('Port database tidak valid.');
        $db = new AppDatabase("pgsql:host=$host;port=$port;dbname=$name;sslmode=$ssl;connect_timeout=10", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => true,
            PDO::ATTR_PERSISTENT => false,
        ]);
        return $db;
    } catch (Throwable $e) {
        error_log('Database connection failed (' . $e->getCode() . '). Check PostgreSQL environment variables.');
        http_response_code(503);
        exit('Koneksi database gagal. Periksa environment database Supabase dan ekstensi pdo_pgsql.');
    }
}

function db_query(AppDatabase $db, string $sql, array $params = [])
{
    $keys = ['app_users'=>'id_user', 'mobil'=>'id_mobil', 'motor'=>'id_motor',
        'log_penyewaan'=>'id_sewa', 'log_sewa_motor'=>'id_sewa', 'notifikasi_admin'=>'id_notif',
        'notifikasi_customer'=>'id_notif', 'bukti_bayar'=>'id', 'denda_biaya'=>'id_denda',
        'checklist_kendaraan'=>'id_checklist', 'chat_messages'=>'id', 'promo_voucher'=>'id_promo',
        'review_kendaraan'=>'id_review', 'tripay_transactions'=>'id'];
    $insert = preg_match('/^\s*INSERT\s+INTO\s+(\w+)/i', $sql, $match) && isset($keys[strtolower($match[1])]);
    if ($insert) $sql = rtrim($sql, "; \r\n") . ' RETURNING ' . $keys[strtolower($match[1])];
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    if ($insert) {
        $db->insertId = (int)$stmt->fetchColumn();
        return true;
    }
    return $stmt->columnCount() ? new AppResult($stmt->fetchAll()) : true;
}

function db_fetch_assoc(AppResult $result): ?array { return $result->rows[$result->position++] ?? null; }
function db_num_rows(AppResult $result): int { return count($result->rows); }
function db_data_seek(AppResult $result, int $position): bool { $result->position = $position; return true; }
function db_real_escape_string(AppDatabase $db, string $value): string { return substr($db->quote($value), 1, -1); }
function db_insert_id(AppDatabase $db): int { return $db->insertId; }
function db_begin_transaction(AppDatabase $db): bool { return $db->beginTransaction(); }
function db_commit(AppDatabase $db): bool { return $db->commit(); }
function db_rollback(AppDatabase $db): bool { return $db->inTransaction() ? $db->rollBack() : true; }
