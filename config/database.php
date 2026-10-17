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

function app_database_failure_code(Throwable $error): string
{
    $message = strtolower($error->getMessage());
    $configurationCodes = ['DB_ENV_MISSING', 'DB_URL_INVALID', 'DB_CONFIG_INVALID', 'DB_PORT_INVALID', 'DB_DRIVER_MISSING'];
    if (in_array($error->getMessage(), $configurationCodes, true)) return $error->getMessage();
    if (str_contains($message, 'could not find driver')) return 'DB_DRIVER_MISSING';
    if (str_contains($message, 'tenant or user not found')) return 'DB_POOLER_INVALID';
    if (str_contains($message, 'password authentication failed') || str_contains($message, 'no password supplied')) return 'DB_AUTH_FAILED';
    if (str_contains($message, 'could not translate host name') || str_contains($message, 'name or service not known')) return 'DB_DNS_FAILED';
    if (str_contains($message, 'timeout') || str_contains($message, 'timed out')) return 'DB_TIMEOUT';
    if (str_contains($message, 'ssl') || str_contains($message, 'certificate')) return 'DB_SSL_FAILED';
    if (str_contains($message, 'connection refused') || str_contains($message, 'network is unreachable')) return 'DB_NETWORK_FAILED';
    if (str_contains($message, 'too many connections') || str_contains($message, 'maxclients')) return 'DB_CONNECTION_LIMIT';
    return 'DB_CONNECT_FAILED';
}

function app_database(): AppDatabase
{
    static $db;
    if ($db instanceof AppDatabase) return $db;
    try {
        $url = trim(app_env('DATABASE_URL') ?? '', " \t\n\r\0\x0B\"'");
        if ($url) {
            $parts = parse_url($url);
            if (!$parts || !in_array($parts['scheme'] ?? '', ['postgres', 'postgresql'], true)) {
                throw new RuntimeException('DB_URL_INVALID');
            }
            $host = $parts['host'] ?? '';
            $port = $parts['port'] ?? 6543;
            $name = rawurldecode(ltrim($parts['path'] ?? '/postgres', '/'));
            $user = rawurldecode($parts['user'] ?? '');
            $pass = rawurldecode($parts['pass'] ?? '');
            parse_str($parts['query'] ?? '', $options);
            $ssl = $options['sslmode'] ?? (app_env('DB_SSLMODE') ?: 'require');
        } else {
            $host = trim(app_env('DB_HOST') ?: '');
            $port = trim(app_env('DB_PORT') ?: '6543');
            $name = trim(app_env('DB_NAME') ?: 'postgres');
            $user = trim(app_env('DB_USER') ?: '');
            $pass = app_env('DB_PASS') ?: '';
            $ssl = trim(app_env('DB_SSLMODE') ?: 'require');
        }
        if ($host === '' || $user === '' || $pass === '') throw new RuntimeException('DB_ENV_MISSING');
        foreach ([$host, $name, $ssl] as $value) {
            if (preg_match('/[;\r\n]/', $value)) throw new RuntimeException('DB_CONFIG_INVALID');
        }
        if (!ctype_digit((string)$port) || (int)$port < 1 || (int)$port > 65535) throw new RuntimeException('DB_PORT_INVALID');
        if (!in_array('pgsql', PDO::getAvailableDrivers(), true)) throw new RuntimeException('DB_DRIVER_MISSING');
        $db = new AppDatabase("pgsql:host=$host;port=$port;dbname=$name;sslmode=$ssl;connect_timeout=10", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => true,
            PDO::ATTR_PERSISTENT => false,
        ]);
        return $db;
    } catch (Throwable $e) {
        $code = app_database_failure_code($e);
        // Log only known categories. Driver messages may contain credentials or connection strings.
        error_log('Database connection failed [' . $code . '].');
        http_response_code(503);
        exit('Koneksi database gagal [' . $code . ']. Periksa environment database Supabase di Vercel dan redeploy.');
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
