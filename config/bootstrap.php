<?php
require_once __DIR__ . '/database.php';

final class DatabaseSessionHandler implements SessionHandlerInterface
{
    public function open(string $path, string $name): bool { return true; }
    public function close(): bool { return true; }
    public function read(string $id): string|false
    {
        $stmt = app_database()->prepare('SELECT payload FROM app_sessions WHERE id = ? AND expires_at > CURRENT_TIMESTAMP');
        $stmt->execute([$id]);
        return $stmt->fetchColumn() ?: '';
    }
    public function write(string $id, string $data): bool
    {
        $stmt = app_database()->prepare("INSERT INTO app_sessions (id, payload, expires_at)
            VALUES (?, ?, CURRENT_TIMESTAMP + make_interval(secs => ?))
            ON CONFLICT (id) DO UPDATE SET payload = EXCLUDED.payload, expires_at = EXCLUDED.expires_at");
        return $stmt->execute([$id, $data, (int)ini_get('session.gc_maxlifetime')]);
    }
    public function destroy(string $id): bool
    {
        $stmt = app_database()->prepare('DELETE FROM app_sessions WHERE id = ?');
        return $stmt->execute([$id]);
    }
    public function gc(int $max_lifetime): int|false
    {
        return app_database()->exec('DELETE FROM app_sessions WHERE expires_at < CURRENT_TIMESTAMP');
    }
}

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_set_cookie_params([
        'httponly' => true, 'samesite' => 'Lax', 'path' => '/',
        'secure' => getenv('VERCEL') === '1' || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    ]);
    session_set_save_handler(new DatabaseSessionHandler(), true);
    session_start();
}
