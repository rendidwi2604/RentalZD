</main>
<style>
@keyframes cfSlide{from{opacity:0;transform:translateY(-10px)}to{opacity:1;transform:translateY(0)}}
</style>
<script>
function toggleSidebar(){
  document.getElementById('sidebar').classList.toggle('open');
  document.getElementById('sidebar-overlay').classList.toggle('open');
}
function closeSidebar(){
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('sidebar-overlay').classList.remove('open');
}

/* ═══════════════════════════════════════════════════════════
   ZD RENT — Custom Confirm Dialog (menggantikan native browser
   confirm() agar tidak muncul popup "localhost says" / 
   "[site] says" yang mengganggu UX pengguna)
═══════════════════════════════════════════════════════════ */
window.showConfirm = function(msg, onOk, onCancel) {
  const overlay = document.createElement('div');
  overlay.style.cssText = 'position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.52);display:flex;align-items:center;justify-content:center;padding:1rem;animation:cfSlide .15s ease';
  overlay.innerHTML = `
    <div style="background:#fff;border-radius:1rem;padding:1.75rem;max-width:400px;width:100%;box-shadow:0 20px 60px rgba(0,0,0,.3)">
      <div style="display:flex;align-items:flex-start;gap:.875rem;margin-bottom:1.25rem">
        <div style="width:40px;height:40px;border-radius:50%;background:#fff3cd;display:flex;align-items:center;justify-content:center;flex-shrink:0">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2.5"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        </div>
        <div>
          <div style="font-weight:700;font-size:.9375rem;margin-bottom:.35rem;color:#111">Konfirmasi</div>
          <div style="font-size:.875rem;color:#555;line-height:1.6">${msg}</div>
        </div>
      </div>
      <div style="display:flex;gap:.625rem;justify-content:flex-end">
        <button id="znr-cf-cancel" style="padding:.625rem 1.25rem;border:1.5px solid #e0e0e0;border-radius:.625rem;background:#fff;font-size:.875rem;font-weight:600;cursor:pointer;color:#555">Batal</button>
        <button id="znr-cf-ok" style="padding:.625rem 1.25rem;border:none;border-radius:.625rem;background:#111;color:#fff;font-size:.875rem;font-weight:700;cursor:pointer">Ya, Lanjutkan</button>
      </div>
    </div>`;
  document.body.appendChild(overlay);
  function close(){ if(document.body.contains(overlay)) document.body.removeChild(overlay); }
  overlay.querySelector('#znr-cf-ok').onclick    = function(){ close(); if(onOk) onOk(); };
  overlay.querySelector('#znr-cf-cancel').onclick = function(){ close(); if(onCancel) onCancel(); };
  overlay.addEventListener('click', function(e){ if(e.target===overlay){ close(); if(onCancel) onCancel(); }});
};

/* Intercept data-confirm pada semua elemen (link & tombol) */
document.addEventListener('DOMContentLoaded', function(){
  /* Click handler untuk elemen dengan data-confirm */
  document.body.addEventListener('click', function(e){
    const el = e.target.closest('[data-confirm]');
    if (!el) return;
    const msg = el.getAttribute('data-confirm');
    if (!msg) return;
    e.preventDefault();
    e.stopImmediatePropagation();
    showConfirm(msg, function(){
      el.removeAttribute('data-confirm');
      if (el.tagName === 'A'){
        window.location.href = el.href;
      } else if (el.closest('form')){
        const form = el.closest('form');
        form._znrConfirmed = true;
        form.submit();
      } else {
        el.click();
      }
    });
  }, true);

  /* Submit handler untuk form yang tombol submitnya punya data-confirm */
  document.body.addEventListener('submit', function(e){
    const form = e.target;
    if (form._znrConfirmed){ form._znrConfirmed = false; return; }
    const btn = form.querySelector('[data-confirm]');
    if (!btn) return;
    e.preventDefault();
    const msg = btn.getAttribute('data-confirm');
    showConfirm(msg, function(){
      form._znrConfirmed = true;
      form.submit();
    });
  }, true);

  /* Log reset form khusus */
  const resetForm = document.getElementById('form-reset-log');
  if (resetForm){
    resetForm.addEventListener('submit', function(e){
      if (this._znrConfirmed){ this._znrConfirmed = false; return; }
      e.preventDefault();
      const form = this;
      showConfirm('Reset SEMUA log penyewaan? Tindakan ini tidak dapat dibatalkan!', function(){
        form._znrConfirmed = true;
        form.submit();
      });
    });
  }
});

/* ═══════════════════════════════════════════════════════════
   Notifikasi badge polling — update jumlah unread setiap 15 detik
   tanpa refresh halaman
═══════════════════════════════════════════════════════════ */
(function pollNotifBadge(){
  const badge = document.getElementById('notif-badge');
  if (!badge) return;
  function fetch_count(){
    fetch('notifikasi.php?count_unread=1')
      .then(function(r){ return r.json(); })
      .then(function(d){
        if (d.count > 0){
          badge.textContent = d.count > 99 ? '99+' : d.count;
          badge.style.display = 'flex';
        } else {
          badge.style.display = 'none';
        }
      })
      .catch(function(){});
  }
  // Poll setiap 15 detik
  setTimeout(function loop(){ fetch_count(); setTimeout(loop, 15000); }, 15000);
})();
</script>
</body>
</html>
