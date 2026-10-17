<?php
require_once __DIR__ . '/config/bootstrap.php';
if (!isset($_SESSION['id_user'])) { header('Location: login.php'); exit; }
require_once 'koneksi.php';
$page_title  = 'Tentang ZD RENT';
$active_menu = 'about';
include '_layout.php';
?>

<!-- ═══════════════════════════════════════════════════════
     HERO SECTION
══════════════════════════════════════════════════════════ -->
<div class="ab-hero">
  <div class="ab-hero__bg">
    <div class="ab-orb ab-orb--1"></div>
    <div class="ab-orb ab-orb--2"></div>
    <div class="ab-orb ab-orb--3"></div>
    <div class="ab-grid-lines"></div>
  </div>

  <div class="ab-hero__inner">
    <div class="ab-hero__left">
      <div class="ab-eyebrow">
        <span class="ab-dot"></span>
        ZD RENT · Tangerang, Banten
      </div>
      <h1 class="ab-hero__title">
        Rental Kendaraan<br>
        <span class="ab-gradient-text">Modern</span> &amp;<br>
        <span class="ab-gradient-text">Terpercaya</span>
      </h1>
      <p class="ab-hero__desc">
        ZD RENT hadir untuk memberikan pengalaman sewa kendaraan yang cepat, aman, dan berkesan.
        Dari mobil keluarga hingga motor harian — semua dalam satu platform profesional.
      </p>
      <div class="ab-hero__cta">
        <?php if ($_SESSION['role'] === 'customer'): ?>
        <a href="pesan_mobil.php" class="ab-btn-primary">🚗 Sewa Mobil Sekarang</a>
        <a href="pesan_motor.php" class="ab-btn-outline">🏍️ Sewa Motor</a>
        <?php else: ?>
        <a href="rentalmobil.php" class="ab-btn-primary">📋 Kelola Armada</a>
        <a href="laporan_mobil.php" class="ab-btn-outline">📊 Lihat Laporan</a>
        <?php endif; ?>
      </div>
    </div>

    <div class="ab-hero__right">
      <div class="ab-hero__visual">
        <!-- Animated car SVG -->
        <div class="ab-vehicle-showcase">
          <svg class="ab-car-svg" viewBox="0 0 420 220" xmlns="http://www.w3.org/2000/svg">
            <defs>
              <linearGradient id="hcg1" x1="0%" y1="0%" x2="0%" y2="100%">
                <stop offset="0%" style="stop-color:#E63946"/>
                <stop offset="100%" style="stop-color:#9b1c27"/>
              </linearGradient>
              <linearGradient id="hcg2" x1="0%" y1="0%" x2="0%" y2="100%">
                <stop offset="0%" style="stop-color:#c1121f"/>
                <stop offset="100%" style="stop-color:#7a0a14"/>
              </linearGradient>
              <radialGradient id="hcwh" cx="50%" cy="50%" r="50%">
                <stop offset="0%" style="stop-color:#888"/>
                <stop offset="60%" style="stop-color:#333"/>
                <stop offset="100%" style="stop-color:#111"/>
              </radialGradient>
              <filter id="hcgl"><feGaussianBlur stdDeviation="3" result="blur"/><feComposite in="SourceGraphic" in2="blur"/></filter>
            </defs>
            <!-- Road -->
            <rect x="0" y="196" width="420" height="24" fill="rgba(255,255,255,0.05)" rx="4"/>
            <line x1="0" y1="208" x2="420" y2="208" stroke="rgba(255,255,255,0.15)" stroke-width="2" stroke-dasharray="30,20"/>
            <!-- Shadow -->
            <ellipse cx="210" cy="200" rx="160" ry="10" fill="rgba(0,0,0,0.4)"/>
            <!-- Body lower -->
            <path d="M55 148 L55 170 Q55 182 68 182 L352 182 Q365 182 365 170 L365 148 Z" fill="url(#hcg2)"/>
            <!-- Body main -->
            <path d="M50 112 L62 85 Q67 74 78 74 L108 71 L145 52 Q162 42 210 42 Q262 42 278 52 L312 71 L342 74 Q353 74 358 85 L370 112 L370 155 L50 155 Z" fill="url(#hcg1)"/>
            <!-- Roof -->
            <path d="M145 52 Q162 42 210 42 Q262 42 278 52 L312 71 L108 71 Z" fill="url(#hcg2)"/>
            <!-- Glass front -->
            <path d="M282 69 L312 71 L352 110 L300 110 Q290 110 285 102 L270 74 Z" fill="rgba(200,240,255,0.35)" stroke="rgba(255,255,255,0.3)" stroke-width="1"/>
            <!-- Glass rear -->
            <path d="M138 69 L108 71 L68 110 L120 110 Q130 110 135 102 L150 74 Z" fill="rgba(200,240,255,0.35)" stroke="rgba(255,255,255,0.3)" stroke-width="1"/>
            <!-- Side windows -->
            <path d="M156 71 L163 56 L205 55 L205 71 Z" fill="rgba(200,240,255,0.4)" stroke="rgba(255,255,255,0.2)" stroke-width="1"/>
            <path d="M210 55 L260 56 L268 71 L210 71 Z" fill="rgba(200,240,255,0.4)" stroke="rgba(255,255,255,0.2)" stroke-width="1"/>
            <!-- Door lines -->
            <line x1="205" y1="74" x2="205" y2="152" stroke="rgba(255,255,255,0.2)" stroke-width="1.5"/>
            <line x1="155" y1="80" x2="155" y2="152" stroke="rgba(255,255,255,0.2)" stroke-width="1.5"/>
            <line x1="268" y1="80" x2="268" y2="152" stroke="rgba(255,255,255,0.2)" stroke-width="1.5"/>
            <!-- Door handles -->
            <rect x="168" y="120" width="22" height="4" rx="2" fill="rgba(255,255,255,0.4)"/>
            <rect x="215" y="120" width="22" height="4" rx="2" fill="rgba(255,255,255,0.4)"/>
            <rect x="278" y="120" width="22" height="4" rx="2" fill="rgba(255,255,255,0.4)"/>
            <!-- Headlights -->
            <path d="M348 94 L365 108 L365 122 L346 122 Q342 122 340 118 L337 103 Z" fill="#fffde0" opacity="0.9"/>
            <circle cx="350" cy="111" r="5" fill="#ffff00" opacity="0.6"/>
            <!-- Glow from headlights -->
            <ellipse cx="390" cy="112" rx="30" ry="15" fill="#ffff00" opacity="0.07" filter="url(#hcgl)"/>
            <!-- Taillights -->
            <path d="M72 94 L55 108 L55 122 L74 122 Q78 122 80 118 L83 103 Z" fill="#ff4444" opacity="0.9"/>
            <!-- Grille -->
            <path d="M340 130 L365 130 L365 146 L340 146 Z" fill="rgba(0,0,0,0.6)" rx="2"/>
            <line x1="348" y1="130" x2="348" y2="146" stroke="rgba(255,255,255,0.1)" stroke-width="1"/>
            <line x1="356" y1="130" x2="356" y2="146" stroke="rgba(255,255,255,0.1)" stroke-width="1"/>
            <line x1="340" y1="137" x2="365" y2="137" stroke="rgba(255,255,255,0.1)" stroke-width="1"/>
            <!-- ZD emblem -->
            <circle cx="338" cy="111" r="7" fill="rgba(255,255,255,0.9)" stroke="#ccc" stroke-width="1"/>
            <text x="338" y="115" text-anchor="middle" font-size="6" font-weight="900" fill="#c1121f">ZD</text>
            <!-- Highlight -->
            <path d="M165 68 Q210 50 255 68" stroke="rgba(255,255,255,0.35)" stroke-width="3" fill="none" stroke-linecap="round"/>
            <!-- Wheels -->
            <circle cx="110" cy="182" r="34" fill="#111"/>
            <circle cx="110" cy="182" r="25" fill="url(#hcwh)"/>
            <circle cx="110" cy="182" r="15" fill="#2a2a2a"/>
            <circle cx="110" cy="182" r="6" fill="#555"/>
            <line x1="110" y1="159" x2="110" y2="205" stroke="#666" stroke-width="2.5"/>
            <line x1="87" y1="182" x2="133" y2="182" stroke="#666" stroke-width="2.5"/>
            <line x1="94" y1="165" x2="126" y2="199" stroke="#666" stroke-width="2"/>
            <line x1="126" y1="165" x2="94" y2="199" stroke="#666" stroke-width="2"/>
            <circle cx="310" cy="182" r="34" fill="#111"/>
            <circle cx="310" cy="182" r="25" fill="url(#hcwh)"/>
            <circle cx="310" cy="182" r="15" fill="#2a2a2a"/>
            <circle cx="310" cy="182" r="6" fill="#555"/>
            <line x1="310" y1="159" x2="310" y2="205" stroke="#666" stroke-width="2.5"/>
            <line x1="287" y1="182" x2="333" y2="182" stroke="#666" stroke-width="2.5"/>
            <line x1="294" y1="165" x2="326" y2="199" stroke="#666" stroke-width="2"/>
            <line x1="326" y1="165" x2="294" y2="199" stroke="#666" stroke-width="2"/>
          </svg>

          <!-- Floating stats badges -->
          <div class="ab-float-badge ab-float-badge--1">⚡ Booking dalam 2 menit</div>
          <div class="ab-float-badge ab-float-badge--2">🛡️ Asuransi Inklusif</div>
          <div class="ab-float-badge ab-float-badge--3">📍 Ketanggungan, Jateng</div>
        </div>

        <!-- Mini stats -->
        <div class="ab-mini-stats">
          <div class="ab-mini-stat"><div class="ab-mini-num">50+</div><div class="ab-mini-lbl">Pelanggan</div></div>
          <div class="ab-mini-divider"></div>
          <div class="ab-mini-stat"><div class="ab-mini-num">14</div><div class="ab-mini-lbl">Unit Armada</div></div>
          <div class="ab-mini-divider"></div>
          <div class="ab-mini-stat"><div class="ab-mini-num">4.9★</div><div class="ab-mini-lbl">Rating</div></div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════════
     WHY ZD RENT
══════════════════════════════════════════════════════════ -->
<div class="ab-section">
  <div class="ab-section-header">
    <div class="ab-section-eyebrow">Keunggulan Kami</div>
    <h2 class="ab-section-title">Kenapa Pilih ZD RENT?</h2>
    <p class="ab-section-desc">Kami tidak hanya menyewakan kendaraan — kami memberikan pengalaman perjalanan yang nyaman dan berkesan.</p>
  </div>
  <div class="ab-features-grid">
    <div class="ab-feature-card ab-feature-card--primary">
      <div class="ab-feature-icon">⚡</div>
      <h3>Booking Super Cepat</h3>
      <p>Proses pemesanan selesai dalam hitungan menit. Pilih kendaraan, isi tanggal, konfirmasi — langsung jadi.</p>
    </div>
    <div class="ab-feature-card">
      <div class="ab-feature-icon">🚗</div>
      <h3>Armada Lengkap</h3>
      <p>Mobil keluarga, city car, SUV, hingga berbagai jenis motor tersedia siap pakai dan terawat.</p>
    </div>
    <div class="ab-feature-card">
      <div class="ab-feature-icon">💰</div>
      <h3>Harga Transparan</h3>
      <p>Tidak ada biaya tersembunyi. Semua biaya ditampilkan secara jelas sebelum konfirmasi booking.</p>
    </div>
    <div class="ab-feature-card">
      <div class="ab-feature-icon">🛡️</div>
      <h3>Terpercaya &amp; Aman</h3>
      <p>Armada kami selalu dalam kondisi prima. Setiap unit diperiksa secara berkala oleh teknisi berpengalaman.</p>
    </div>
    <div class="ab-feature-card">
      <div class="ab-feature-icon">📱</div>
      <h3>Responsif di Semua Device</h3>
      <p>Akses platform kami dari HP, tablet, atau laptop — tampilan selalu nyaman dan fungsional.</p>
    </div>
    <div class="ab-feature-card">
      <div class="ab-feature-icon">🎧</div>
      <h3>Support 24/7</h3>
      <p>Tim customer service kami siap membantu kapan saja via WhatsApp, setiap hari tanpa libur.</p>
    </div>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════════
     FLEET SHOWCASE
══════════════════════════════════════════════════════════ -->
<div class="ab-fleet-banner">
  <div class="ab-fleet-banner__content">
    <div class="ab-fleet-banner__text">
      <div class="ab-eyebrow" style="color:rgba(255,255,255,.5)">Armada Kami</div>
      <h2>Mobil &amp; Motor Pilihan<br>untuk Setiap Perjalanan</h2>
      <p>Dari Avanza untuk keluarga besar, Brio untuk perjalanan efisien, hingga Innova premium — semua ada di ZD RENT.</p>
      <div class="ab-fleet-chips">
        <span class="ab-fleet-chip">Toyota Avanza</span>
        <span class="ab-fleet-chip">Honda Brio</span>
        <span class="ab-fleet-chip">Toyota Innova</span>
        <span class="ab-fleet-chip">Yamaha NMAX</span>
        <span class="ab-fleet-chip">Honda PCX</span>
        <span class="ab-fleet-chip">+9 Lainnya</span>
      </div>
    </div>
    <div class="ab-fleet-banner__visual">
      <!-- Motor SVG -->
      <svg viewBox="0 0 320 180" xmlns="http://www.w3.org/2000/svg" class="ab-motor-svg">
        <defs>
          <linearGradient id="motBg" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" style="stop-color:#E63946"/>
            <stop offset="100%" style="stop-color:#9b1c27"/>
          </linearGradient>
          <radialGradient id="motWh" cx="50%" cy="50%" r="50%">
            <stop offset="0%" style="stop-color:#666"/>
            <stop offset="70%" style="stop-color:#222"/>
            <stop offset="100%" style="stop-color:#111"/>
          </radialGradient>
        </defs>
        <ellipse cx="160" cy="168" rx="120" ry="9" fill="rgba(0,0,0,0.3)"/>
        <!-- Rear wheel -->
        <circle cx="222" cy="148" r="34" fill="#111"/>
        <circle cx="222" cy="148" r="25" fill="url(#motWh)"/>
        <circle cx="222" cy="148" r="14" fill="#2a2a2a"/>
        <circle cx="222" cy="148" r="6" fill="#444"/>
        <line x1="222" y1="126" x2="222" y2="170" stroke="#555" stroke-width="2.5"/>
        <line x1="200" y1="148" x2="244" y2="148" stroke="#555" stroke-width="2.5"/>
        <line x1="206" y1="130" x2="238" y2="166" stroke="#555" stroke-width="2"/>
        <line x1="238" y1="130" x2="206" y2="166" stroke="#555" stroke-width="2"/>
        <!-- Front wheel -->
        <circle cx="84" cy="148" r="32" fill="#111"/>
        <circle cx="84" cy="148" r="23" fill="url(#motWh)"/>
        <circle cx="84" cy="148" r="13" fill="#2a2a2a"/>
        <circle cx="84" cy="148" r="5" fill="#444"/>
        <line x1="84" y1="128" x2="84" y2="168" stroke="#555" stroke-width="2.5"/>
        <line x1="64" y1="148" x2="104" y2="148" stroke="#555" stroke-width="2.5"/>
        <line x1="70" y1="132" x2="98" y2="164" stroke="#555" stroke-width="2"/>
        <line x1="98" y1="132" x2="70" y2="164" stroke="#555" stroke-width="2"/>
        <!-- Frame -->
        <path d="M222 148 Q195 140 168 135 L148 115 L130 110 L110 118" stroke="#c1121f" stroke-width="6" fill="none" stroke-linecap="round"/>
        <!-- Body -->
        <path d="M110 110 L142 98 L172 100 L182 112 L178 126 L148 132 L118 128 Z" fill="url(#motBg)"/>
        <path d="M142 98 L178 102 L180 98 L170 90 Q158 84 148 87 Z" fill="#9b1c27"/>
        <!-- Seat -->
        <path d="M160 100 Q192 95 215 98 L218 108 Q196 105 165 112 Z" fill="#1a1a1a"/>
        <!-- Rear fairing -->
        <path d="M208 98 L222 105 L230 120 L222 135 L208 130 L198 115 Z" fill="url(#motBg)"/>
        <path d="M218 106 L230 118" stroke="#ff6666" stroke-width="2" opacity="0.7"/>
        <!-- Taillight -->
        <rect x="225" y="119" width="7" height="10" rx="2" fill="#ff4444" opacity="0.9"/>
        <!-- Fork -->
        <line x1="110" y1="118" x2="88" y2="148" stroke="#c1121f" stroke-width="5" stroke-linecap="round"/>
        <line x1="100" y1="112" x2="78" y2="142" stroke="#e63946" stroke-width="3" stroke-linecap="round"/>
        <!-- Handlebar -->
        <line x1="110" y1="110" x2="130" y2="100" stroke="#333" stroke-width="5" stroke-linecap="round"/>
        <circle cx="130" cy="100" r="5" fill="#E63946"/>
        <!-- Headlight -->
        <path d="M97" fill="#fffde0"/><path d="M100 110 L112 100 L118 104 L108 116 Z" fill="#fffde0"/>
        <!-- Accent stripe -->
        <path d="M118 124 Q148 118 178 122" stroke="rgba(255,255,255,0.4)" stroke-width="2.5" fill="none" stroke-linecap="round"/>
      </svg>
    </div>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════════
     CONTACT & SOCIAL
══════════════════════════════════════════════════════════ -->
<div class="ab-section">
  <div class="ab-section-header">
    <div class="ab-section-eyebrow">Hubungi Kami</div>
    <h2 class="ab-section-title">Siap Melayani Anda</h2>
  </div>
  <div class="ab-contact-grid">
    <!-- WhatsApp -->
    <a href="https://wa.me/6289668660248" target="_blank" rel="noopener" class="ab-contact-card ab-contact-card--wa">
      <div class="ab-contact-icon">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
      </div>
      <div class="ab-contact-info">
        <div class="ab-contact-label">WhatsApp</div>
        <div class="ab-contact-value">+62 896-6866-0248</div>
        <div class="ab-contact-sub">Chat langsung — respon cepat</div>
      </div>
      <div class="ab-contact-arrow">→</div>
    </a>

    <!-- Email -->
    <a href="mailto:support@znrent.com" class="ab-contact-card">
      <div class="ab-contact-icon" style="background:#f0f4ff;color:#4264cc">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
      </div>
      <div class="ab-contact-info">
        <div class="ab-contact-label">Email</div>
        <div class="ab-contact-value">support@znrent.com</div>
        <div class="ab-contact-sub">Respon dalam 24 jam</div>
      </div>
    </a>

    <!-- Lokasi -->
    <div class="ab-contact-card">
      <div class="ab-contact-icon" style="background:#f0fff4;color:#2d6a4f">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
      </div>
      <div class="ab-contact-info">
        <div class="ab-contact-label">Lokasi</div>
        <div class="ab-contact-value">Tangerang</div>
        <div class="ab-contact-sub">Banten, Indonesia</div>
      </div>
    </div>

    <!-- Jam Operasional -->
    <div class="ab-contact-card">
      <div class="ab-contact-icon" style="background:#fffbeb;color:#b45309">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
      </div>
      <div class="ab-contact-info">
        <div class="ab-contact-label">Jam Operasional</div>
        <div class="ab-contact-value">08.00 – 22.00 WIB</div>
        <div class="ab-contact-sub">Setiap hari termasuk hari libur</div>
      </div>
    </div>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════════
     SOCIAL MEDIA
══════════════════════════════════════════════════════════ -->
<div class="ab-social-section">
  <div class="ab-social-inner">
    <div class="ab-social-text">
      <h2>Ikuti ZD RENT di Sosial Media</h2>
      <p>Update armada terbaru, promo spesial, dan tips perjalanan ada di sini.</p>
    </div>
    <div class="ab-social-links">
      <a href="#" class="ab-social-card">
        <div class="ab-social-icon" style="background:linear-gradient(135deg,#f09433,#e6683c,#dc2743,#cc2366,#bc1888)">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="white"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
        </div>
        <div><div style="font-weight:700">Instagram</div><div style="font-size:var(--text-xs);color:var(--color-text-muted)">@znrent.id</div></div>
      </a>
      <a href="#" class="ab-social-card">
        <div class="ab-social-icon" style="background:#000">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="white"><path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.89-2.89 2.89 2.89 0 012.89-2.89c.28 0 .54.04.79.1V9.01a6.32 6.32 0 00-.79-.05 6.34 6.34 0 00-6.34 6.34 6.34 6.34 0 006.34 6.34 6.34 6.34 0 006.34-6.34V8.69a8.24 8.24 0 004.84 1.55V6.79a4.85 4.85 0 01-1.08-.1z"/></svg>
        </div>
        <div><div style="font-weight:700">TikTok</div><div style="font-size:var(--text-xs);color:var(--color-text-muted)">@znrent.id</div></div>
      </a>
      <a href="#" class="ab-social-card">
        <div class="ab-social-icon" style="background:#ff0000">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="white"><path d="M23.498 6.186a3.016 3.016 0 00-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 00.502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 002.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 002.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
        </div>
        <div><div style="font-weight:700">YouTube</div><div style="font-size:var(--text-xs);color:var(--color-text-muted)">ZD RENT Official</div></div>
      </a>
      <a href="#" class="ab-social-card">
        <div class="ab-social-icon" style="background:#1877f2">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="white"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
        </div>
        <div><div style="font-weight:700">Facebook</div><div style="font-size:var(--text-xs);color:var(--color-text-muted)">ZD RENT Official</div></div>
      </a>
    </div>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════════
     CTA STRIP
══════════════════════════════════════════════════════════ -->
<div class="ab-cta-strip">
  <div class="ab-cta-strip__text">
    <h2>Siap untuk Memulai Perjalanan?</h2>
    <p>Booking kendaraan impian Anda sekarang dan nikmati perjalanan tanpa khawatir.</p>
  </div>
  <div class="ab-cta-strip__actions">
    <?php if ($_SESSION['role'] === 'customer'): ?>
    <a href="pesan_mobil.php" class="ab-btn-primary">Sewa Mobil →</a>
    <a href="pesan_motor.php" class="ab-btn-outline" style="border-color:rgba(255,255,255,.3);color:#fff">Sewa Motor →</a>
    <?php else: ?>
    <a href="rentalmobil.php" class="ab-btn-primary">Kelola Armada →</a>
    <?php endif; ?>
  </div>
</div>

<style>
/* ─── GENERAL ─────────────────────────────────────── */
.ab-eyebrow{
  display:inline-flex;
  align-items:center;
  gap:8px;
  font-size:var(--text-xs);
  font-weight:700;
  text-transform:uppercase;
  letter-spacing:.12em;
  color:rgba(255,255,255,.65);
  margin-bottom:var(--space-4)
}

.ab-dot{
  width:6px;
  height:6px;
  border-radius:50%;
  background:#60a5fa;
  flex-shrink:0;
  animation:pulse 2s ease-in-out infinite
}

@keyframes pulse{
  0%,100%{opacity:1;transform:scale(1)}
  50%{opacity:.5;transform:scale(1.3)}
}

.ab-gradient-text{
  background:linear-gradient(135deg,#60a5fa,#bfdbfe);
  -webkit-background-clip:text;
  -webkit-text-fill-color:transparent;
  background-clip:text
}

.ab-btn-primary{
  display:inline-flex;
  align-items:center;
  gap:8px;
  padding:.85rem 1.6rem;
  background:#2563eb;
  color:#fff;
  border-radius:999px;
  font-weight:800;
  font-size:var(--text-sm);
  text-decoration:none;
  transition:all var(--transition);
  box-shadow:0 4px 20px rgba(37,99,235,.35)
}

.ab-btn-primary:hover{
  background:#1d4ed8;
  transform:translateY(-2px);
  box-shadow:0 8px 30px rgba(37,99,235,.45)
}

.ab-btn-outline{
  display:inline-flex;
  align-items:center;
  gap:8px;
  padding:.85rem 1.6rem;
  background:transparent;
  color:#fff;
  border:2px solid rgba(255,255,255,.3);
  border-radius:999px;
  font-weight:700;
  font-size:var(--text-sm);
  text-decoration:none;
  transition:all var(--transition)
}

.ab-btn-outline:hover{
  background:rgba(255,255,255,.1);
  border-color:rgba(255,255,255,.6)
}


/* ─── HERO ────────────────────────────────────────── */
.ab-hero{
  position:relative;
  background:linear-gradient(135deg,#0f2f6b 0%,#123d87 50%,#1d4ed8 100%);
  border-radius:1.75rem;
  overflow:hidden;
  margin-bottom:var(--space-8);
  min-height:520px
}

.ab-hero__bg{
  position:absolute;
  inset:0;
  pointer-events:none
}

.ab-orb{
  position:absolute;
  border-radius:50%;
  filter:blur(80px)
}

.ab-orb--1{
  width:350px;
  height:350px;
  background:rgba(96,165,250,.20);
  top:-80px;
  right:-60px
}

.ab-orb--2{
  width:250px;
  height:250px;
  background:rgba(37,99,235,.15);
  bottom:-40px;
  left:20%
}

.ab-orb--3{
  width:200px;
  height:200px;
  background:rgba(191,219,254,.12);
  bottom:40px;
  right:30%
}

.ab-grid-lines{
  position:absolute;
  inset:0;
  background-image:
    linear-gradient(rgba(255,255,255,.04) 1px,transparent 1px),
    linear-gradient(90deg,rgba(255,255,255,.04) 1px,transparent 1px);
  background-size:40px 40px
}

.ab-hero__inner{
  position:relative;
  z-index:1;
  display:grid;
  grid-template-columns:1fr 1fr;
  gap:var(--space-8);
  padding:clamp(2rem,4vw,3.5rem);
  align-items:center;
  min-height:520px
}

.ab-hero__title{
  font-size:clamp(2.2rem,3vw+1rem,3.8rem);
  font-weight:800;
  line-height:1.06;
  letter-spacing:-.04em;
  color:#fff;
  margin-bottom:var(--space-5)
}

.ab-hero__desc{
  color:rgba(255,255,255,.68);
  font-size:var(--text-base);
  line-height:1.75;
  max-width:46ch;
  margin-bottom:var(--space-6)
}

.ab-hero__cta{
  display:flex;
  gap:var(--space-3);
  flex-wrap:wrap
}


/* ─── VEHICLE SHOWCASE ─────────────────────────────── */
.ab-vehicle-showcase{
  position:relative
}

.ab-car-svg{
  width:100%;
  max-width:420px;
  display:block;
  filter:drop-shadow(0 20px 40px rgba(37,99,235,.35));
  animation:carFloat 4s ease-in-out infinite
}

@keyframes carFloat{
  0%,100%{transform:translateY(0)}
  50%{transform:translateY(-10px)}
}

.ab-float-badge{
  position:absolute;
  background:rgba(255,255,255,.12);
  backdrop-filter:blur(12px);
  border:1px solid rgba(255,255,255,.2);
  border-radius:999px;
  padding:.45rem .9rem;
  font-size:11px;
  font-weight:700;
  color:#fff;
  white-space:nowrap
}

.ab-float-badge--1{
  top:-10px;
  right:0;
  animation:badgeFloat 3s ease-in-out infinite
}

.ab-float-badge--2{
  bottom:40px;
  left:-10px;
  animation:badgeFloat 3s ease-in-out infinite .8s
}

.ab-float-badge--3{
  bottom:0;
  right:20px;
  animation:badgeFloat 3s ease-in-out infinite 1.5s
}

@keyframes badgeFloat{
  0%,100%{transform:translateY(0)}
  50%{transform:translateY(-5px)}
}

.ab-mini-stats{
  display:flex;
  align-items:center;
  gap:var(--space-4);
  margin-top:var(--space-5);
  background:rgba(255,255,255,.08);
  border:1px solid rgba(255,255,255,.15);
  border-radius:1rem;
  padding:var(--space-4)
}

.ab-mini-stat{
  text-align:center
}

.ab-mini-num{
  font-size:1.5rem;
  font-weight:800;
  color:#fff;
  line-height:1
}

.ab-mini-lbl{
  font-size:10px;
  color:rgba(255,255,255,.55);
  text-transform:uppercase;
  letter-spacing:.08em;
  margin-top:4px
}

.ab-mini-divider{
  width:1px;
  height:36px;
  background:rgba(255,255,255,.15)
}


/* ─── SECTION ────────────────────────────────────── */
.ab-section{
  margin-bottom:var(--space-8)
}

.ab-section-header{
  text-align:center;
  margin-bottom:var(--space-8)
}

.ab-section-eyebrow{
  display:inline-flex;
  padding:.4rem .9rem;
  background:#eff6ff;
  color:#1d4ed8;
  border-radius:999px;
  font-size:var(--text-xs);
  font-weight:700;
  margin-bottom:var(--space-4)
}

.ab-section-title{
  font-size:clamp(1.8rem,2.5vw+.5rem,2.8rem);
  font-weight:800;
  letter-spacing:-.03em;
  margin-bottom:var(--space-4)
}

.ab-section-desc{
  color:var(--color-text-muted);
  max-width:50ch;
  margin:0 auto;
  font-size:var(--text-base);
  line-height:1.7
}


/* ─── FEATURES ───────────────────────────────────── */
.ab-features-grid{
  display:grid;
  grid-template-columns:repeat(3,1fr);
  gap:var(--space-5)
}

.ab-feature-card{
  background:#fff;
  border:1px solid rgba(37,99,235,.12);
  border-radius:1.25rem;
  padding:var(--space-6);
  transition:transform var(--transition),box-shadow var(--transition)
}

.ab-feature-card:hover{
  transform:translateY(-4px);
  box-shadow:var(--shadow-lg)
}

.ab-feature-card--primary{
  background:linear-gradient(135deg,#1d4ed8,#2563eb);
  color:#fff;
  border-color:#2563eb
}

.ab-feature-card--primary p{
  color:rgba(255,255,255,.78)
}

.ab-feature-icon{
  font-size:2rem;
  margin-bottom:var(--space-4)
}

.ab-feature-card h3{
  font-size:var(--text-base);
  font-weight:800;
  margin-bottom:var(--space-3)
}

.ab-feature-card p{
  font-size:var(--text-sm);
  color:var(--color-text-muted);
  line-height:1.65
}


/* ─── FLEET BANNER ────────────────────────────────── */
.ab-fleet-banner{
  background:linear-gradient(135deg,#0f2f6b 0%,#123d87 55%,#1d4ed8 100%);
  border-radius:1.75rem;
  padding:clamp(1.5rem,4vw,3rem);
  margin-bottom:var(--space-8);
  overflow:hidden;
  position:relative
}

.ab-fleet-banner::before{
  content:'';
  position:absolute;
  right:-100px;
  top:-100px;
  width:400px;
  height:400px;
  background:radial-gradient(
    circle,
    rgba(147,197,253,.2) 0%,
    transparent 70%
  )
}

.ab-fleet-banner__content{
  display:grid;
  grid-template-columns:1fr 1fr;
  gap:var(--space-6);
  align-items:center;
  position:relative;
  z-index:1
}

.ab-fleet-banner__text h2{
  font-size:clamp(1.8rem,2.5vw+.5rem,2.6rem);
  font-weight:800;
  letter-spacing:-.03em;
  color:#fff;
  margin-bottom:var(--space-4)
}

.ab-fleet-banner__text p{
  color:rgba(255,255,255,.68);
  line-height:1.7;
  margin-bottom:var(--space-5)
}

.ab-fleet-chips{
  display:flex;
  flex-wrap:wrap;
  gap:var(--space-2)
}

.ab-fleet-chip{
  display:inline-flex;
  padding:.4rem .85rem;
  background:rgba(255,255,255,.1);
  border:1px solid rgba(255,255,255,.18);
  border-radius:999px;
  color:rgba(255,255,255,.88);
  font-size:var(--text-xs);
  font-weight:600
}

.ab-motor-svg{
  width:100%;
  max-width:320px;
  display:block;
  margin:0 auto;
  filter:drop-shadow(0 16px 30px rgba(37,99,235,.35));
  animation:carFloat 3.5s ease-in-out infinite .5s
}


/* ─── CONTACT ──────────────────────────────────────── */
.ab-contact-grid{
  display:grid;
  grid-template-columns:repeat(2,1fr);
  gap:var(--space-4)
}

.ab-contact-card{
  display:flex;
  align-items:center;
  gap:var(--space-4);
  padding:var(--space-5);
  background:#fff;
  border:1px solid rgba(37,99,235,.12);
  border-radius:1.25rem;
  text-decoration:none;
  color:var(--color-text);
  transition:transform var(--transition),box-shadow var(--transition),border-color var(--transition)
}

.ab-contact-card:hover{
  transform:translateY(-3px);
  box-shadow:var(--shadow-md);
  border-color:#2563eb
}

/* WhatsApp tetap hijau karena warna identitas WhatsApp */
.ab-contact-card--wa{
  background:linear-gradient(135deg,#ecfdf5,#d1fae5);
  border-color:#a7f3d0
}

.ab-contact-card--wa:hover{
  border-color:#10b981
}

.ab-contact-icon{
  width:56px;
  height:56px;
  border-radius:1rem;
  background:#eff6ff;
  color:#2563eb;
  display:flex;
  align-items:center;
  justify-content:center;
  flex-shrink:0
}

.ab-contact-card--wa .ab-contact-icon{
  background:#25D366;
  color:#fff
}

.ab-contact-label{
  font-size:var(--text-xs);
  color:var(--color-text-faint);
  text-transform:uppercase;
  letter-spacing:.08em;
  font-weight:700;
  margin-bottom:4px
}

.ab-contact-value{
  font-size:var(--text-base);
  font-weight:800;
  margin-bottom:2px
}

.ab-contact-sub{
  font-size:var(--text-xs);
  color:var(--color-text-muted)
}

.ab-contact-arrow{
  font-size:1.25rem;
  margin-left:auto;
  color:#2563eb
}


/* ─── SOCIAL ──────────────────────────────────────── */
.ab-social-section{
  background:#f8fbff;
  border:1px solid rgba(37,99,235,.12);
  border-radius:1.5rem;
  padding:clamp(1.5rem,3vw,2.5rem);
  margin-bottom:var(--space-8)
}

.ab-social-inner{
  display:grid;
  grid-template-columns:1fr 1fr;
  gap:var(--space-6);
  align-items:center
}

.ab-social-text h2{
  font-size:var(--text-xl);
  font-weight:800;
  letter-spacing:-.02em;
  margin-bottom:var(--space-3)
}

.ab-social-text p{
  color:var(--color-text-muted);
  font-size:var(--text-sm);
  line-height:1.65
}

.ab-social-links{
  display:grid;
  grid-template-columns:1fr 1fr;
  gap:var(--space-3)
}

.ab-social-card{
  display:flex;
  align-items:center;
  gap:var(--space-3);
  padding:var(--space-4);
  background:#fff;
  border:1px solid rgba(37,99,235,.12);
  border-radius:1rem;
  text-decoration:none;
  color:var(--color-text);
  transition:all var(--transition)
}

.ab-social-card:hover{
  transform:translateY(-2px);
  box-shadow:var(--shadow-md);
  border-color:rgba(37,99,235,.3)
}

.ab-social-icon{
  width:44px;
  height:44px;
  border-radius:.75rem;
  display:flex;
  align-items:center;
  justify-content:center;
  flex-shrink:0
}


/* ─── CTA STRIP ──────────────────────────────────── */
.ab-cta-strip{
  background:linear-gradient(135deg,#1d4ed8,#2563eb,#3b82f6);
  border-radius:1.5rem;
  padding:clamp(1.5rem,3vw,2.5rem);
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:var(--space-6);
  flex-wrap:wrap;
  margin-bottom:var(--space-6)
}

.ab-cta-strip h2{
  font-size:clamp(1.4rem,1.5vw+.5rem,2rem);
  font-weight:800;
  color:#fff;
  margin-bottom:var(--space-2);
  letter-spacing:-.02em
}

.ab-cta-strip p{
  color:rgba(255,255,255,.75);
  font-size:var(--text-sm)
}

.ab-cta-strip__actions{
  display:flex;
  gap:var(--space-3);
  flex-wrap:wrap;
  flex-shrink:0
}

.ab-cta-strip .ab-btn-primary{
  background:#fff;
  color:#1d4ed8;
  box-shadow:none
}

.ab-cta-strip .ab-btn-primary:hover{
  background:#eff6ff;
  color:#1e40af
}


/* ─── RESPONSIVE ─────────────────────────────────── */
@media(max-width:1100px){
  .ab-features-grid{
    grid-template-columns:repeat(2,1fr)
  }
}

@media(max-width:900px){
  .ab-hero__inner,
  .ab-fleet-banner__content,
  .ab-social-inner{
    grid-template-columns:1fr
  }

  .ab-hero__right{
    display:none
  }

  .ab-contact-grid{
    grid-template-columns:1fr
  }

  .ab-social-links{
    grid-template-columns:1fr
  }
}

@media(max-width:600px){
  .ab-features-grid{
    grid-template-columns:1fr
  }

  .ab-hero__title{
    font-size:2rem
  }

  .ab-cta-strip{
    flex-direction:column;
    text-align:center
  }

  .ab-cta-strip__actions{
    justify-content:center;
    width:100%
  }
}
</style>

<?php include '_layout_footer.php'; ?>
