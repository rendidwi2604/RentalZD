<?php
require_once __DIR__ . '/config/bootstrap.php';
if (!isset($_SESSION['id_user'])) { header('Location: login.php'); exit; }
require_once 'koneksi.php';

// Auto-create chat table


$id_user = (int)$_SESSION['id_user'];
$role    = $_SESSION['role'];

// AJAX: kirim pesan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kirim'])) {
    header('Content-Type: application/json');
    $pesan = trim($_POST['pesan'] ?? '');
    if ($pesan === '') { echo json_encode(['ok'=>false]); exit; }
    $esc = db_real_escape_string($conn, $pesan);

    if ($role === 'admin') {
        // Admin kirim ke customer tertentu
        $to = intval($_POST['to'] ?? 0);
        if (!$to) { echo json_encode(['ok'=>false]); exit; }
        db_query($conn, "INSERT INTO chat_messages (id_sender,id_receiver,pesan) VALUES ('$id_user','$to','$esc')");
    } else {
        // Customer kirim ke admin (id_receiver = NULL berarti ke semua admin)
        db_query($conn, "INSERT INTO chat_messages (id_sender,id_receiver,pesan) VALUES ('$id_user',NULL,'$esc')");
    }
    echo json_encode(['ok'=>true]);
    exit;
}

// AJAX: ambil pesan
if (isset($_GET['fetch'])) {
    header('Content-Type: application/json');
    $after = intval($_GET['after'] ?? 0);

    if ($role === 'admin') {
        $cust_id = intval($_GET['cust'] ?? 0);
        if (!$cust_id) { echo json_encode([]); exit; }
        $q = db_query($conn, "
            SELECT cm.*, u.nama_lengkap, u.role
            FROM chat_messages cm
            LEFT JOIN app_users u ON cm.id_sender=u.id_user
            WHERE cm.id > '$after'
              AND (
                (cm.id_sender='$cust_id' AND cm.id_receiver IS NULL)
                OR (cm.id_sender='$id_user' AND cm.id_receiver='$cust_id')
              )
            ORDER BY cm.created_at ASC LIMIT 50");
        // Tandai sudah dibaca
        db_query($conn, "UPDATE chat_messages SET sudah_baca=1
            WHERE id_sender='$cust_id' AND id_receiver IS NULL AND sudah_baca=0");
    } else {
        $q = db_query($conn, "
            SELECT cm.*, u.nama_lengkap, u.role
            FROM chat_messages cm
            LEFT JOIN app_users u ON cm.id_sender=u.id_user
            WHERE cm.id > '$after'
              AND (cm.id_sender='$id_user' OR (cm.id_receiver='$id_user'))
              OR (cm.id_sender='$id_user' AND cm.id_receiver IS NULL)
            ORDER BY cm.created_at ASC LIMIT 50");
        $q = db_query($conn, "
            SELECT cm.*, u.nama_lengkap, u.role
            FROM chat_messages cm
            LEFT JOIN app_users u ON cm.id_sender=u.id_user
            WHERE cm.id > '$after'
              AND (
                cm.id_sender='$id_user'
                OR (cm.id_receiver='$id_user')
                OR (cm.id_sender!='$id_user' AND cm.id_receiver IS NULL AND u.role='admin')
              )
            ORDER BY cm.created_at ASC LIMIT 50");
        db_query($conn, "UPDATE chat_messages SET sudah_baca=1
            WHERE id_receiver='$id_user' AND sudah_baca=0");
    }
    $rows = [];
    while ($r = db_fetch_assoc($q)) $rows[] = $r;
    echo json_encode($rows);
    exit;
}

// AJAX: unread count
if (isset($_GET['unread'])) {
    header('Content-Type: application/json');
    if ($role === 'admin') {
        $cnt = db_fetch_assoc(db_query($conn,
            "SELECT COUNT(*) as c FROM chat_messages WHERE id_receiver IS NULL AND sudah_baca=0"))['c'] ?? 0;
    } else {
        $cnt = db_fetch_assoc(db_query($conn,
            "SELECT COUNT(*) as c FROM chat_messages WHERE id_receiver='$id_user' AND sudah_baca=0"))['c'] ?? 0;
    }
    echo json_encode(['count' => (int)$cnt]);
    exit;
}

// Daftar customer (untuk admin)
$customers = [];
if ($role === 'admin') {
    $q = db_query($conn, "
        SELECT u.id_user, u.nama_lengkap, u.username,
               (SELECT COUNT(*) FROM chat_messages WHERE id_sender=u.id_user AND id_receiver IS NULL AND sudah_baca=0) AS unread
        FROM app_users u WHERE u.role='customer' ORDER BY u.nama_lengkap");
    while ($r = db_fetch_assoc($q)) $customers[] = $r;
}

$page_title  = 'Chat';
$active_menu = 'chat';
include '_layout.php';
?>

<style>
.chat-wrap{display:grid;grid-template-columns:<?= $role==='admin'?'260px 1fr':'1fr'?>;gap:0;height:calc(100dvh - var(--header-h) - var(--space-8)*2);max-height:700px;border-radius:var(--radius-xl);overflow:hidden;border:1px solid var(--color-border);background:#fff}
.chat-sidebar{border-right:1px solid var(--color-border);display:flex;flex-direction:column;background:var(--color-surface)}
.chat-sidebar-header{padding:var(--space-4);font-weight:800;font-size:var(--text-sm);border-bottom:1px solid var(--color-border)}
.chat-list{flex:1;overflow-y:auto}
.chat-list-item{padding:var(--space-3) var(--space-4);cursor:pointer;border-bottom:1px solid var(--color-divider);transition:background var(--transition);display:flex;align-items:center;gap:var(--space-3)}
.chat-list-item:hover,.chat-list-item.active{background:var(--color-primary-highlight)}
.chat-list-item.active{border-left:3px solid var(--color-accent)}
.chat-avatar{width:36px;height:36px;border-radius:50%;background:#111;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:var(--text-xs);flex-shrink:0}
.chat-main{display:flex;flex-direction:column}
.chat-header{padding:var(--space-4) var(--space-5);border-bottom:1px solid var(--color-border);display:flex;align-items:center;gap:var(--space-3);background:#fff}
.chat-messages{flex:1;overflow-y:auto;padding:var(--space-5);display:flex;flex-direction:column;gap:var(--space-3);background:var(--color-surface)}
.chat-bubble{max-width:72%;padding:var(--space-3) var(--space-4);border-radius:1rem;font-size:var(--text-sm);line-height:1.55;position:relative}
.chat-bubble.mine{background:#111;color:#fff;align-self:flex-end;border-bottom-right-radius:.25rem}
.chat-bubble.theirs{background:#fff;border:1px solid var(--color-border);align-self:flex-start;border-bottom-left-radius:.25rem}
.chat-bubble .time{font-size:10px;opacity:.5;margin-top:4px;display:block}
.chat-input-wrap{padding:var(--space-4);border-top:1px solid var(--color-border);display:flex;gap:var(--space-3);background:#fff}
.chat-input{flex:1;padding:var(--space-3) var(--space-4);border:1.5px solid var(--color-border);border-radius:var(--radius-full);font-size:var(--text-sm);outline:none;transition:border-color var(--transition)}
.chat-input:focus{border-color:#111}
.chat-send-btn{width:40px;height:40px;border-radius:50%;background:#111;color:#fff;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:background var(--transition)}
.chat-send-btn:hover{background:#333}
.chat-empty{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:var(--space-3);color:var(--color-text-faint)}
.unread-badge{min-width:18px;height:18px;padding:0 5px;background:#E63946;color:#fff;border-radius:999px;font-size:10px;font-weight:800;display:flex;align-items:center;justify-content:center;margin-left:auto}
</style>

<div style="margin-bottom:var(--space-5)">
  <h2 style="font-size:var(--text-xl);font-weight:800;letter-spacing:-.02em">💬 Chat</h2>
  <p style="color:var(--color-text-muted);font-size:var(--text-sm)"><?= $role==='admin'?'Komunikasi dengan customer':'Chat langsung dengan admin ZD RENT' ?></p>
</div>

<div class="chat-wrap">

<?php if ($role === 'admin'): ?>
<!-- SIDEBAR LIST CUSTOMER -->
<div class="chat-sidebar">
  <div class="chat-sidebar-header">Daftar Customer</div>
  <div class="chat-list" id="customer-list">
    <?php foreach ($customers as $c): ?>
    <div class="chat-list-item" data-id="<?= $c['id_user'] ?>" data-nama="<?= htmlspecialchars($c['nama_lengkap']) ?>" onclick="selectCustomer(this)">
      <div class="chat-avatar"><?= strtoupper(substr($c['nama_lengkap'],0,1)) ?></div>
      <div style="flex:1;min-width:0">
        <div style="font-weight:700;font-size:var(--text-xs);white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= htmlspecialchars($c['nama_lengkap']) ?></div>
        <div style="font-size:10px;color:var(--color-text-faint)">@<?= htmlspecialchars($c['username']) ?></div>
      </div>
      <?php if ($c['unread'] > 0): ?>
      <span class="unread-badge"><?= $c['unread'] ?></span>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
    <?php if (empty($customers)): ?>
    <div style="padding:var(--space-5);text-align:center;color:var(--color-text-faint);font-size:var(--text-xs)">Belum ada customer</div>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<!-- MAIN CHAT AREA -->
<div class="chat-main">
  <div class="chat-header" id="chat-header">
    <?php if ($role === 'admin'): ?>
    <div style="color:var(--color-text-faint);font-size:var(--text-sm)">← Pilih customer untuk mulai chat</div>
    <?php else: ?>
    <div class="chat-avatar" style="background:#E63946">ZD</div>
    <div>
      <div style="font-weight:700;font-size:var(--text-sm)">Admin ZD RENT</div>
      <div style="font-size:11px;color:var(--color-text-faint)">Siap membantu Anda</div>
    </div>
    <?php endif; ?>
  </div>

  <div class="chat-messages" id="chat-messages">
    <?php if ($role === 'admin'): ?>
    <div class="chat-empty" id="chat-empty">
      <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
      <p style="font-size:var(--text-sm)">Pilih customer untuk melihat percakapan</p>
    </div>
    <?php else: ?>
    <div class="chat-empty" id="chat-empty" style="display:none"></div>
    <?php endif; ?>
  </div>

  <div class="chat-input-wrap" id="chat-input-wrap" <?= $role==='admin'?'style="display:none"':'' ?>>
    <input type="text" class="chat-input" id="chat-input" placeholder="Ketik pesan..." autocomplete="off" onkeydown="if(event.key==='Enter')sendMsg()">
    <button class="chat-send-btn" onclick="sendMsg()">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
    </button>
  </div>
</div>

</div>

<script>
const ROLE      = '<?= $role ?>';
const MY_ID     = <?= $id_user ?>;
const MY_NAMA   = '<?= addslashes($_SESSION['nama_lengkap']) ?>';
let currentCust = <?= $role === 'customer' ? 'null' : 'null' ?>;
let lastId      = 0;
let pollTimer   = null;

<?php if ($role === 'customer'): ?>
// Customer: langsung load chat
loadMessages();
startPolling();
<?php endif; ?>

function selectCustomer(el) {
  document.querySelectorAll('.chat-list-item').forEach(e => e.classList.remove('active'));
  el.classList.add('active');
  // Clear unread badge
  const badge = el.querySelector('.unread-badge');
  if (badge) badge.remove();

  currentCust = el.dataset.id;
  const nama  = el.dataset.nama;

  document.getElementById('chat-header').innerHTML = `
    <div class="chat-avatar">${nama.charAt(0).toUpperCase()}</div>
    <div>
      <div style="font-weight:700;font-size:var(--text-sm)">${nama}</div>
      <div style="font-size:11px;color:var(--color-text-faint)">Customer</div>
    </div>`;
  document.getElementById('chat-input-wrap').style.display = 'flex';
  document.getElementById('chat-empty').style.display = 'none';

  lastId = 0;
  document.getElementById('chat-messages').innerHTML = '';

  clearInterval(pollTimer);
  loadMessages();
  startPolling();
}

function loadMessages() {
  const url = ROLE === 'admin'
    ? `chat.php?fetch=1&after=${lastId}&cust=${currentCust}`
    : `chat.php?fetch=1&after=${lastId}`;

  if (ROLE === 'admin' && !currentCust) return;

  fetch(url)
    .then(r => r.json())
    .then(msgs => {
      const box = document.getElementById('chat-messages');
      msgs.forEach(m => {
        if (m.id <= lastId) return;
        lastId = Math.max(lastId, parseInt(m.id));
        const mine = parseInt(m.id_sender) === MY_ID;
        const time = new Date(m.created_at).toLocaleTimeString('id-ID',{hour:'2-digit',minute:'2-digit'});
        const div = document.createElement('div');
        div.className = 'chat-bubble ' + (mine ? 'mine' : 'theirs');
        div.innerHTML = `${!mine?`<div style="font-size:10px;font-weight:700;margin-bottom:4px;opacity:.6">${m.nama_lengkap}</div>`:''}${escHtml(m.pesan)}<span class="time">${time}</span>`;
        box.appendChild(div);
      });
      if (msgs.length > 0) box.scrollTop = box.scrollHeight;
    });
}

function startPolling() {
  clearInterval(pollTimer);
  pollTimer = setInterval(loadMessages, 3000);
}

function sendMsg() {
  const input = document.getElementById('chat-input');
  const pesan = input.value.trim();
  if (!pesan) return;
  if (ROLE === 'admin' && !currentCust) return;

  const body = new FormData();
  body.append('kirim', '1');
  body.append('pesan', pesan);
  if (ROLE === 'admin') body.append('to', currentCust);
  input.value = '';

  fetch('chat.php', { method:'POST', body })
    .then(r => r.json())
    .then(d => { if (d.ok) loadMessages(); });
}

function escHtml(t) {
  return t.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\n/g,'<br>');
}
</script>

<?php include '_layout_footer.php'; ?>
