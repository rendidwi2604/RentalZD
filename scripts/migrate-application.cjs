const fs = require('node:fs');
const path = require('node:path');
const root = path.resolve(__dirname, '..');
for (const file of fs.readdirSync(root).filter(f => f.endsWith('.php'))) {
  let text = fs.readFileSync(path.join(root, file), 'utf8').replace(/^\uFEFF/, '');
  // Schema is installed explicitly once, never by a web request.
  text = text.replace(/@?mysqli_query\(\$conn,\s*"CREATE TABLE IF NOT EXISTS[\s\S]*?"\);/g, '');
  text = text.replace(/^[ \t]*@?mysqli_query\(\$conn,\s*"ALTER TABLE[^\n]*\r?\n/gm, '');
  text = text.replace(/\bmysqli_/g, 'db_');
  text = text.replace(/\bsession_start\(\);/g, "require_once __DIR__ . '/config/bootstrap.php';");
  text = text.replace(/\b(FROM|JOIN|UPDATE|INTO|TABLE) user\b/gi, '$1 app_users');
  text = text.replace(/\bCURDATE\(\)/g, 'CURRENT_DATE');
  text = text.replace(/\b(YEAR|MONTH|DAY)\(([^()]+)\)/g, (_, unit, col) => `EXTRACT(${unit} FROM ${col})`);
  text = text.replace(/DATEDIFF\(([^,]+),\s*([^)]+)\)/g, '($1 - $2)');
  text = text.replace(/DATE_FORMAT\(tanggal_sewa,'%b %Y'\)/g, "TO_CHAR(tanggal_sewa,'Mon YYYY')");
  text = text.replace(/SUM\(status='tersedia'\)/g, "SUM(CASE WHEN status='tersedia' THEN 1 ELSE 0 END)");
  text = text.replace(/FIELD\(status,'tersedia','disewa'\)/g, "CASE status WHEN 'tersedia' THEN 1 WHEN 'disewa' THEN 2 ELSE 0 END");
  // Replace the small number of mysqli prepared calls with native PDO execution.
  text = text.replace(/db_prepare\(\$conn,\s*/g, '$conn->prepare(');
  text = text.replace(/\s*db_stmt_bind_param\(\$stmt, '[^']*', ([^;]+)\);\s*db_stmt_execute\(\$stmt\)/g,
    (_, args) => `\n        $stmt->execute([${args}])`);
  text = text.replace(/\$result = db_stmt_get_result\(\$stmt\);\s*\$user = db_fetch_assoc\(\$result\);/g, '$user = $stmt->fetch();');
  if (file === 'login.php') text = text.replace("if ($user && password_verify($password, $user['password'])) {", "if ($user && password_verify($password, $user['password'])) {\n            session_regenerate_id(true);");
  fs.writeFileSync(path.join(root, file), text);
}
console.log('Application migrated to PostgreSQL.');
