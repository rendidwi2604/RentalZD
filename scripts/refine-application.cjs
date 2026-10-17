const fs = require('node:fs');
const path = require('node:path');
const root = path.resolve(__dirname, '..');
for (const file of fs.readdirSync(root).filter(f => f.endsWith('.php'))) {
  let s = fs.readFileSync(path.join(root, file), 'utf8');
  s = s.replace(/NOW\(\)/g, "timezone('Asia/Jakarta', CURRENT_TIMESTAMP)");
  s = s.replace(/\/\/ (?:Auto-create tabel[^\r\n]*|Pastikan tabel motor ada|Tambahkan kolom status_bayar[^\r\n]*|Add columns if not exist|Auto-create tabel bukti bayar)[\r\n]+/g, '');
  if (file === 'notif_helper.php') s = s.replace('@param mysqli', '@param AppDatabase');
  if (file === 'upload_bukti.php') {
    s = s.replace("require_once 'notif_helper.php';", "require_once 'notif_helper.php';\nrequire_once __DIR__ . '/config/storage.php';");
    const start = s.indexOf("        $upload_dir =");
    const end = s.indexOf("\n    }\n}", start);
    if (start < 0 || end < 0) throw Error('Upload replacement boundaries not found');
    s = s.slice(0, start) + `        $table = $jenis === 'mobil' ? 'log_penyewaan' : 'log_sewa_motor';
        $owned = db_fetch_assoc(db_query($conn, "SELECT id_sewa FROM {$table} WHERE id_sewa=? AND id_user=?", [$id_sewa, $id_user]));
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $types = ['jpg'=>'image/jpeg', 'jpeg'=>'image/jpeg', 'png'=>'image/png', 'webp'=>'image/webp', 'pdf'=>'application/pdf'];
        if (!$owned) {
            $msg_error = 'Pesanan tidak ditemukan.';
        } elseif (($types[$ext] ?? '') !== $mime) {
            $msg_error = 'Isi file tidak sesuai format yang diizinkan.';
        } else {
            $filename = 'bukti_'.$jenis.'_'.$id_sewa.'_'.time().'_'.bin2hex(random_bytes(8)).'.'.$ext;
            try {
                storage_upload($file['tmp_name'], $filename, $mime);
                db_begin_transaction($conn);
                db_query($conn, "INSERT INTO bukti_bayar (id_sewa,jenis,id_user,file_path,catatan) VALUES (?,?,?,?,?)",
                    [$id_sewa, $jenis, $id_user, 'uploads/bukti/'.$filename, trim($_POST['catatan'] ?? '')]);
                db_query($conn, "UPDATE {$table} SET payment_status='menunggu_konfirmasi' WHERE id_sewa=? AND id_user=?", [$id_sewa, $id_user]);
                notif_admin($conn, 'pembayaran', $_SESSION['nama_lengkap']." mengupload bukti transfer untuk sewa #{$id_sewa} ({$jenis}). Harap verifikasi.", $id_sewa, $jenis);
                db_commit($conn);
                header('Location: payment_page.php?id='.$id_sewa.'&jenis='.$jenis.'&uploaded=1'); exit;
            } catch (Throwable $e) {
                db_rollback($conn);
                error_log('Receipt upload failed (' . $e->getCode() . ').');
                $msg_error = 'Bukti pembayaran gagal disimpan. Periksa konfigurasi Supabase Storage atau coba lagi.';
            }
        }` + s.slice(end);
    s = s.replace(/5 \* 1024 \* 1024/g, '4 * 1024 * 1024').replace(/5MB/g, '4MB');
  }
  if (file === 'verifikasi_bukti.php') {
    s = s.replace(/htmlspecialchars\(\$b\['file_path'\]\)/g, "htmlspecialchars('bukti_file.php?file=' . rawurlencode(basename($b['file_path'])))");
  }
  if (file === 'payment_callback.php' || file === 'payment_return.php') {
    s = s.replace("require_once 'tripay_helper.php';", "if (!is_file(__DIR__ . '/tripay_helper.php')) { http_response_code(503); exit('Integrasi Tripay belum tersedia.'); }\nrequire_once __DIR__ . '/tripay_helper.php';");
  }
  if (file === 'payment_callback.php') {
    const a = s.indexOf('$log_dir =');
    const b = s.indexOf('// Cari transaksi', a);
    s = s.slice(0, a) + "error_log('Tripay callback: ref=' . $reference . ' status=' . $status);\n\n" + s.slice(b);
  }
  fs.writeFileSync(path.join(root, file), s);
}
