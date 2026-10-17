<?php
// _layout.php — shared navbar + sidebar layout shell
// Usage: include after session_start() + auth check
// Expects: $page_title (string), $active_menu (string)
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title><?= htmlspecialchars($page_title ?? 'ZD RENT') ?> — ZD RENT</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>

/* =========================================================
   TOKENS
========================================================= */

:root {
    --font-body: 'Plus Jakarta Sans', sans-serif;

    --text-xs: clamp(0.75rem, 0.7rem + 0.25vw, 0.875rem);
    --text-sm: clamp(0.875rem, 0.8rem + 0.35vw, 1rem);
    --text-base: clamp(1rem, 0.95rem + 0.25vw, 1.125rem);
    --text-lg: clamp(1.125rem, 1rem + 0.75vw, 1.5rem);
    --text-xl: clamp(1.5rem, 1.2rem + 1.25vw, 2.25rem);

    --space-1: .25rem;
    --space-2: .5rem;
    --space-3: .75rem;
    --space-4: 1rem;
    --space-5: 1.25rem;
    --space-6: 1.5rem;
    --space-8: 2rem;
    --space-10: 2.5rem;
    --space-12: 3rem;

    /* ===== BLUE WHITE THEME ===== */

    --color-bg: #ffffff;
    --color-surface: #f5f9ff;
    --color-surface-2: #eaf2ff;

    --color-border: rgba(37, 99, 235, 0.12);
    --color-divider: rgba(37, 99, 235, 0.07);

    --color-text: #172033;
    --color-text-muted: #64748b;
    --color-text-faint: #94a3b8;
    --color-text-inverse: #ffffff;

    /* Blue utama */
    --color-primary: #2563eb;
    --color-primary-hover: #1d4ed8;

    /* Accent biru lebih terang */
    --color-accent: #3b82f6;
    --color-accent-hover: #1d4ed8;

    --color-success: #15803d;
    --color-success-bg: #f0fdf4;

    --color-error: #dc2626;
    --color-error-bg: #fef2f2;

    --color-warning: #d97706;
    --color-warning-bg: #fffbeb;

    --sidebar-w: 240px;
    --header-h: 64px;

    --radius-sm: .375rem;
    --radius-md: .5rem;
    --radius-lg: .75rem;
    --radius-xl: 1rem;
    --radius-full: 9999px;

    --shadow-sm: 0 1px 3px rgba(37, 99, 235, 0.08);
    --shadow-md: 0 4px 12px rgba(37, 99, 235, 0.12);
    --shadow-lg: 0 12px 32px rgba(37, 99, 235, 0.15);

    --transition: 180ms cubic-bezier(0.16, 1, 0.3, 1);
}


/* =========================================================
   BASE
========================================================= */

*,
*::before,
*::after {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

html {
    -webkit-font-smoothing: antialiased;
    scroll-behavior: smooth;
}

body {
    font-family: var(--font-body);
    font-size: var(--text-sm);
    color: var(--color-text);
    background: var(--color-surface);
    min-height: 100dvh;
}

img {
    display: block;
    max-width: 100%;
}

input,
button,
select,
textarea {
    font: inherit;
    color: inherit;
}

button {
    cursor: pointer;
    background: none;
    border: none;
}

:focus-visible {
    outline: 2px solid var(--color-primary);
    outline-offset: 3px;
    border-radius: var(--radius-sm);
}

a,
button,
[role="button"],
input,
select {
    transition:
        color var(--transition),
        background var(--transition),
        border-color var(--transition),
        box-shadow var(--transition);
}

table {
    border-collapse: collapse;
    width: 100%;
}


/* =========================================================
   SIDEBAR
========================================================= */

.sidebar {
    position: fixed;
    top: 0;
    left: 0;
    width: var(--sidebar-w);
    height: 100dvh;

    /* Biru tua */
    background: #1e3a8a;

    display: flex;
    flex-direction: column;

    z-index: 100;

    transition: transform var(--transition);

    overflow: hidden;
}


/* ===== BRAND ===== */

.sidebar-brand {
    display: flex;
    align-items: center;
    gap: var(--space-3);

    padding: var(--space-5);

    border-bottom: 1px solid rgba(255, 255, 255, 0.12);

    flex-shrink: 0;
}

.sidebar-logo {
    width: 36px;
    height: 36px;

    /* Biru terang */
    background: #3b82f6;

    border-radius: var(--radius-md);

    display: flex;
    align-items: center;
    justify-content: center;

    flex-shrink: 0;

    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.sidebar-brand-text .name {
    font-size: var(--text-sm);
    font-weight: 800;
    color: #ffffff;
    letter-spacing: -0.01em;
    line-height: 1.2;
}

.sidebar-brand-text .tagline {
    font-size: 10px;
    color: rgba(255, 255, 255, 0.55);
    text-transform: uppercase;
    letter-spacing: 0.08em;
}


/* =========================================================
   SIDEBAR NAVIGATION
========================================================= */

.sidebar-nav {
    flex: 1;

    padding: var(--space-4) var(--space-3);

    overflow-y: auto;
    overflow-x: hidden;

    display: flex;
    flex-direction: column;

    gap: var(--space-1);

    scrollbar-width: thin;
    scrollbar-color: rgba(255, 255, 255, 0.18) transparent;
}

.sidebar-nav::-webkit-scrollbar {
    width: 4px;
}

.sidebar-nav::-webkit-scrollbar-track {
    background: transparent;
}

.sidebar-nav::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.18);
    border-radius: 999px;
}

.sidebar-nav::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.3);
}


/* ===== SECTION LABEL ===== */

.nav-section-label {
    font-size: 10px;
    font-weight: 600;

    color: rgba(255, 255, 255, 0.45);

    text-transform: uppercase;
    letter-spacing: 0.1em;

    padding:
        var(--space-4)
        var(--space-3)
        var(--space-2);
}


/* ===== NAV ITEM ===== */

.nav-item {
    display: flex;
    align-items: center;
    gap: var(--space-3);

    padding:
        var(--space-3)
        var(--space-4);

    border-radius: var(--radius-lg);

    color: rgba(255, 255, 255, 0.72);

    text-decoration: none;

    font-size: var(--text-sm);
    font-weight: 500;

    transition:
        background var(--transition),
        color var(--transition);
}

.nav-item:hover {
    background: rgba(255, 255, 255, 0.10);
    color: #ffffff;
}

.nav-item.active {
    background: #ffffff;
    color: #1e3a8a;
    font-weight: 700;

    box-shadow:
        0 3px 10px rgba(0, 0, 0, 0.12);
}

.nav-item svg {
    flex-shrink: 0;
    opacity: 0.85;
}

.nav-item.active svg {
    opacity: 1;
}


/* =========================================================
   SIDEBAR FOOTER
========================================================= */

.sidebar-footer {
    padding: var(--space-4) var(--space-3);

    border-top: 1px solid rgba(255, 255, 255, 0.12);

    flex-shrink: 0;
}

.user-info {
    display: flex;
    align-items: center;
    gap: var(--space-3);

    padding:
        var(--space-3)
        var(--space-4);

    border-radius: var(--radius-lg);

    margin-bottom: var(--space-2);
}

.user-avatar {
    width: 32px;
    height: 32px;

    background: rgba(255, 255, 255, 0.18);

    border-radius: var(--radius-full);

    display: flex;
    align-items: center;
    justify-content: center;

    font-size: var(--text-xs);
    font-weight: 700;

    color: #ffffff;

    flex-shrink: 0;
}

.user-name {
    font-size: var(--text-xs);
    font-weight: 600;
    color: rgba(255, 255, 255, 0.9);
}

.user-role {
    font-size: 10px;
    color: rgba(255, 255, 255, 0.5);

    text-transform: capitalize;
}


/* ===== LOGOUT ===== */

.logout-btn {
    display: flex;
    align-items: center;
    gap: var(--space-3);

    padding:
        var(--space-3)
        var(--space-4);

    border-radius: var(--radius-lg);

    color: rgba(255, 190, 190, 0.9);

    font-size: var(--text-sm);
    font-weight: 500;

    width: 100%;

    text-decoration: none;

    transition:
        background var(--transition),
        color var(--transition);
}

.logout-btn:hover {
    background: rgba(239, 68, 68, 0.16);
    color: #fecaca;
}


/* =========================================================
   HEADER
========================================================= */

.header {
    position: fixed;

    top: 0;
    left: var(--sidebar-w);
    right: 0;

    height: var(--header-h);

    background: var(--color-bg);

    border-bottom: 1px solid var(--color-border);

    display: flex;
    align-items: center;
    justify-content: space-between;

    padding: 0 var(--space-6);

    z-index: 50;

    box-shadow: 0 1px 5px rgba(37, 99, 235, 0.05);
}

.header-left {
    display: flex;
    align-items: center;
    gap: var(--space-3);
}


/* ===== MENU TOGGLE ===== */

.menu-toggle {
    display: none;

    width: 40px;
    height: 40px;

    border-radius: var(--radius-md);

    align-items: center;
    justify-content: center;

    transition: background var(--transition);

    color: var(--color-primary);
}

.menu-toggle:hover {
    background: var(--color-surface-2);
}


/* ===== PAGE TITLE ===== */

.page-title {
    font-size: var(--text-base);
    font-weight: 700;

    color: var(--color-text);

    letter-spacing: -0.01em;
}

.breadcrumb {
    display: flex;
    align-items: center;

    gap: var(--space-2);

    font-size: var(--text-xs);

    color: var(--color-text-faint);

    margin-top: 1px;
}

.breadcrumb span {
    color: var(--color-text-muted);
}


/* ===== HEADER RIGHT ===== */

.header-right {
    display: flex;
    align-items: center;

    gap: var(--space-3);
}

.header-badge {
    display: flex;
    align-items: center;

    gap: var(--space-2);

    padding:
        var(--space-2)
        var(--space-3);

    background: var(--color-surface);

    border: 1px solid var(--color-border);

    border-radius: var(--radius-full);

    font-size: var(--text-xs);

    color: var(--color-text-muted);
}

.status-dot {
    width: 6px;
    height: 6px;

    border-radius: 50%;

    background: #22c55e;

    box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.12);
}


/* =========================================================
   MAIN CONTENT
========================================================= */

.main-content {
    margin-left: var(--sidebar-w);
    margin-top: var(--header-h);

    padding:
        var(--space-8)
        var(--space-8);

    min-height: calc(100dvh - var(--header-h));
}


/* =========================================================
   OVERLAY
========================================================= */

.sidebar-overlay {
    display: none;

    position: fixed;
    inset: 0;

    background: rgba(15, 23, 42, 0.55);

    z-index: 90;
}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 900px) {

    .sidebar {
        transform:
            translateX(calc(-1 * var(--sidebar-w)));
    }

    .sidebar.open {
        transform: translateX(0);
    }

    .header {
        left: 0;
    }

    .main-content {
        margin-left: 0;
    }

    .menu-toggle {
        display: flex;
    }

    .sidebar-overlay.open {
        display: block;
    }
}


@media (max-width: 640px) {

    .main-content {
        padding:
            var(--space-5)
            var(--space-4);
    }

    .header {
        padding:
            0
            var(--space-4);
    }
}


/* =========================================================
   REUSABLE COMPONENTS
========================================================= */


/* ===== CARDS ===== */

.card {
    background: var(--color-bg);

    border: 1px solid var(--color-border);

    border-radius: var(--radius-xl);

    padding: var(--space-6);

    box-shadow: var(--shadow-sm);
}

.card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;

    margin-bottom: var(--space-5);

    gap: var(--space-4);

    flex-wrap: wrap;
}

.card-title {
    font-size: var(--text-base);

    font-weight: 700;

    color: var(--color-text);

    letter-spacing: -0.01em;
}


/* ===== BUTTONS ===== */

.btn {
    display: inline-flex;
    align-items: center;

    gap: var(--space-2);

    padding:
        var(--space-2)
        var(--space-4);

    border-radius: var(--radius-lg);

    font-size: var(--text-sm);
    font-weight: 600;

    border: none;

    cursor: pointer;

    transition: all var(--transition);

    text-decoration: none;

    white-space: nowrap;
}

.btn-primary {
    background: var(--color-primary);
    color: var(--color-text-inverse);
}

.btn-primary:hover {
    background: var(--color-primary-hover);

    box-shadow: var(--shadow-md);

    transform: translateY(-1px);
}

.btn-primary:active {
    transform: translateY(0);
}

.btn-danger {
    background: var(--color-error);
    color: #ffffff;
}

.btn-danger:hover {
    background: #b91c1c;
    box-shadow: var(--shadow-md);
}

.btn-secondary {
    background: var(--color-surface);

    color: var(--color-primary);

    border: 1px solid var(--color-border);
}

.btn-secondary:hover {
    background: var(--color-surface-2);
}

.btn-ghost {
    background: transparent;

    color: var(--color-text-muted);

    border: 1px solid var(--color-border);
}

.btn-ghost:hover {
    background: var(--color-surface);

    color: var(--color-primary);
}

.btn-sm {
    padding:
        var(--space-1)
        var(--space-3);

    font-size: var(--text-xs);
}

.btn-icon {
    padding: var(--space-2);

    min-width: 36px;

    height: 36px;

    justify-content: center;
}


/* =========================================================
   BADGES
========================================================= */

.badge {
    display: inline-flex;

    align-items: center;

    gap: 4px;

    padding:
        2px
        var(--space-3);

    border-radius: var(--radius-full);

    font-size: var(--text-xs);

    font-weight: 600;

    line-height: 1.6;
}

.badge-success {
    background: var(--color-success-bg);
    color: var(--color-success);
}

.badge-error {
    background: var(--color-error-bg);
    color: var(--color-error);
}

.badge-warning {
    background: var(--color-warning-bg);
    color: var(--color-warning);
}

.badge-neutral {
    background: var(--color-surface-2);
    color: var(--color-text-muted);
}


/* =========================================================
   TABLE
========================================================= */

.table-wrap {
    overflow-x: auto;

    -webkit-overflow-scrolling: touch;

    border-radius: var(--radius-lg);

    border: 1px solid var(--color-border);
}

.data-table {
    width: 100%;
}

.data-table thead tr {
    border-bottom:
        1px solid
        var(--color-border);
}

.data-table th {
    padding:
        var(--space-3)
        var(--space-4);

    text-align: left;

    font-size: var(--text-xs);

    font-weight: 700;

    color: var(--color-text-faint);

    text-transform: uppercase;

    letter-spacing: 0.06em;

    background: var(--color-surface);

    white-space: nowrap;
}

.data-table td {
    padding:
        var(--space-3)
        var(--space-4);

    font-size: var(--text-sm);

    color: var(--color-text);

    border-bottom:
        1px solid
        var(--color-divider);

    vertical-align: middle;
}

.data-table tbody tr:last-child td {
    border-bottom: none;
}

.data-table tbody tr:hover td {
    background: var(--color-surface);
}


/* =========================================================
   FORM
========================================================= */

.form-grid {
    display: grid;

    gap: var(--space-5);
}

.form-row {
    display: grid;

    grid-template-columns: 1fr 1fr;

    gap: var(--space-5);
}

@media (max-width: 600px) {

    .form-row {
        grid-template-columns: 1fr;
    }
}

.form-group {
    display: flex;

    flex-direction: column;

    gap: var(--space-2);
}

.form-label {
    font-size: var(--text-sm);

    font-weight: 600;

    color: var(--color-text);
}

.form-label span {
    color: var(--color-error);

    margin-left: 2px;
}

.form-input,
.form-select,
.form-textarea {
    width: 100%;

    padding:
        var(--space-3)
        var(--space-4);

    border:
        1.5px solid
        var(--color-border);

    border-radius: var(--radius-lg);

    font-size: var(--text-sm);

    background: var(--color-surface);

    color: var(--color-text);

    transition:
        border-color var(--transition),
        box-shadow var(--transition),
        background var(--transition);

    -webkit-appearance: none;
}

.form-input:focus,
.form-select:focus,
.form-textarea:focus {
    outline: none;

    border-color: var(--color-primary);

    background: var(--color-bg);

    box-shadow:
        0 0 0 3px
        rgba(37, 99, 235, 0.10);
}

.form-input::placeholder,
.form-textarea::placeholder {
    color: var(--color-text-faint);
}


/* =========================================================
   ALERTS
========================================================= */

.alert {
    display: flex;

    align-items: flex-start;

    gap: var(--space-3);

    padding: var(--space-4);

    border-radius: var(--radius-lg);

    font-size: var(--text-sm);

    margin-bottom: var(--space-5);
}

.alert-success {
    background: var(--color-success-bg);

    color: var(--color-success);

    border:
        1px solid
        rgba(21, 128, 61, 0.15);
}

.alert-error {
    background: var(--color-error-bg);

    color: var(--color-error);

    border:
        1px solid
        rgba(220, 38, 38, 0.15);
}

.alert-warning {
    background: var(--color-warning-bg);

    color: var(--color-warning);

    border:
        1px solid
        rgba(217, 119, 6, 0.15);
}


/* =========================================================
   STATS
========================================================= */

.stats-grid {
    display: grid;

    grid-template-columns:
        repeat(
            auto-fill,
            minmax(180px, 1fr)
        );

    gap: var(--space-4);

    margin-bottom: var(--space-6);
}

.stat-card {
    background: var(--color-bg);

    border:
        1px solid
        var(--color-border);

    border-radius: var(--radius-xl);

    padding:
        var(--space-5)
        var(--space-6);

    box-shadow: var(--shadow-sm);

    display: flex;

    flex-direction: column;

    gap: var(--space-2);
}

.stat-label {
    font-size: var(--text-xs);

    font-weight: 600;

    color: var(--color-text-faint);

    text-transform: uppercase;

    letter-spacing: 0.06em;
}

.stat-value {
    font-size: var(--text-xl);

    font-weight: 800;

    color: var(--color-primary);

    letter-spacing: -0.02em;

    font-variant-numeric: tabular-nums;
}

.stat-sub {
    font-size: var(--text-xs);

    color: var(--color-text-muted);
}


/* =========================================================
   SKELETON
========================================================= */

@keyframes shimmer {

    0% {
        background-position: -200% 0;
    }

    100% {
        background-position: 200% 0;
    }

}

.skeleton {
    background:
        linear-gradient(
            90deg,
            var(--color-surface) 25%,
            var(--color-surface-2) 50%,
            var(--color-surface) 75%
        );

    background-size: 200% 100%;

    animation:
        shimmer 1.5s
        ease-in-out infinite;

    border-radius: var(--radius-sm);
}


/* =========================================================
   EMPTY STATE
========================================================= */

.empty-state {
    display: flex;

    flex-direction: column;

    align-items: center;

    text-align: center;

    padding:
        var(--space-12)
        var(--space-8);

    color: var(--color-text-muted);
}

.empty-state svg {
    margin-bottom: var(--space-4);

    color: var(--color-text-faint);
}

.empty-state h3 {
    color: var(--color-text);

    font-size: var(--text-base);

    font-weight: 700;

    margin-bottom: var(--space-2);
}

.empty-state p {
    max-width: 36ch;

    font-size: var(--text-sm);
}

</style>
</head>

<body>

<!-- =====================================================
     SIDEBAR OVERLAY
===================================================== -->

<div
    class="sidebar-overlay"
    id="sidebar-overlay"
    onclick="closeSidebar()">
</div>


<!-- =====================================================
     SIDEBAR
===================================================== -->

<aside class="sidebar" id="sidebar">

    <!-- BRAND -->
    <div class="sidebar-brand">

        <div class="sidebar-logo">

            <svg
                width="20"
                height="20"
                viewBox="0 0 24 24"
                fill="none"
                stroke="white"
                stroke-width="2.5"
                stroke-linecap="round"
                stroke-linejoin="round">

                <path d="M5 17H3a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2v3"/>

                <rect
                    x="9"
                    y="11"
                    width="14"
                    height="10"
                    rx="2"/>

                <circle
                    cx="12"
                    cy="16"
                    r="1"/>

                <circle
                    cx="20"
                    cy="16"
                    r="1"/>

            </svg>

        </div>

        <div class="sidebar-brand-text">

            <div class="name">
                ZD RENT
            </div>

            <div class="tagline">
                Premium Car Rental
            </div>

        </div>

    </div>


    <!-- NAVIGATION -->

    <nav class="sidebar-nav">

        <?php

        // Hitung badge notifikasi untuk admin

        $notif_count = 0;
        $pesanan_baru_count = 0;

        if (
            isset($_SESSION['role']) &&
            $_SESSION['role'] === 'admin'
        ) {

            if (!isset($conn)) {
                require_once __DIR__ . '/koneksi.php';
            }

            $nb1 = db_fetch_assoc(
                db_query(
                    $conn,
                    "SELECT COUNT(*) as c
                     FROM log_penyewaan
                     WHERE admin_confirmed=0"
                )
            );

            $nb2 = db_fetch_assoc(
                db_query(
                    $conn,
                    "SELECT COUNT(*) as c
                     FROM log_sewa_motor
                     WHERE admin_confirmed=0"
                )
            );

            $np1 = db_fetch_assoc(
                db_query(
                    $conn,
                    "SELECT COUNT(*) as c
                     FROM log_penyewaan
                     WHERE payment_status='pending'
                     AND admin_confirmed=1"
                )
            );

            $np2 = db_fetch_assoc(
                db_query(
                    $conn,
                    "SELECT COUNT(*) as c
                     FROM log_sewa_motor
                     WHERE payment_status='pending'
                     AND admin_confirmed=1"
                )
            );

            $pesanan_baru_count =
                ($nb1['c'] ?? 0)
                +
                ($nb2['c'] ?? 0);

            $notif_count =
                $pesanan_baru_count
                +
                ($np1['c'] ?? 0)
                +
                ($np2['c'] ?? 0);
        }

        ?>


        <?php if ($_SESSION['role'] === 'admin'): ?>


        <!-- =================================================
             ADMIN MENU
        ================================================= -->

        <div class="nav-section-label">
            Admin Panel
        </div>


        <!-- KONFIRMASI PESANAN -->

        <a
            href="konfirmasi_pesanan.php"
            class="nav-item <?= ($active_menu ?? '') === 'konfirmasi' ? 'active' : '' ?>"
            style="justify-content:space-between">

            <span
                style="
                    display:flex;
                    align-items:center;
                    gap:var(--space-3)
                ">

                <svg
                    width="16"
                    height="16"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2">

                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>

                    <polyline points="22 4 12 14.01 9 11.01"/>

                </svg>

                Konfirmasi Pesanan

            </span>


            <?php if ($notif_count > 0): ?>

            <span
                style="
                    min-width:20px;
                    height:20px;
                    padding:0 6px;
                    background:#ef4444;
                    color:#fff;
                    border-radius:999px;
                    font-size:10px;
                    font-weight:800;
                    display:flex;
                    align-items:center;
                    justify-content:center;
                    flex-shrink:0
                ">

                <?= $notif_count ?>

            </span>

            <?php endif; ?>

        </a>


        <!-- DATA MOBIL -->

        <a
            href="rentalmobil.php"
            class="nav-item <?= ($active_menu ?? '') === 'mobil' ? 'active' : '' ?>">

            <svg
                width="16"
                height="16"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2">

                <path d="M5 17H3a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2v3"/>

                <rect
                    x="9"
                    y="11"
                    width="14"
                    height="10"
                    rx="2"/>

                <circle
                    cx="12"
                    cy="16"
                    r="1"/>

                <circle
                    cx="20"
                    cy="16"
                    r="1"/>

            </svg>

            Data Mobil

        </a>


        <!-- DATA MOTOR -->

        <a
            href="rental_motor.php"
            class="nav-item <?= ($active_menu ?? '') === 'motor' ? 'active' : '' ?>">

            <svg
                width="16"
                height="16"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2">

                <circle
                    cx="18.5"
                    cy="17.5"
                    r="3.5"/>

                <circle
                    cx="5.5"
                    cy="17.5"
                    r="3.5"/>

                <path d="M15 6H4L2 12h14l1-6z"/>

                <path d="M15 6l2 6h4l-2-6h-4z"/>

            </svg>

            Data Motor

        </a>


        <!-- MANAJEMEN USER -->

        <a
            href="user_management.php"
            class="nav-item <?= ($active_menu ?? '') === 'users' ? 'active' : '' ?>">

            <svg
                width="16"
                height="16"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2">

                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>

                <circle
                    cx="9"
                    cy="7"
                    r="4"/>

                <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>

                <path d="M16 3.13a4 4 0 0 1 0 7.75"/>

            </svg>

            Manajemen User

        </a>


        <!-- LOG PENYEWAAN -->

        <a
            href="log_penyewaan.php"
            class="nav-item <?= ($active_menu ?? '') === 'log' ? 'active' : '' ?>">

            <svg
                width="16"
                height="16"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2">

                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>

                <polyline points="14 2 14 8 20 8"/>

                <line
                    x1="16"
                    y1="13"
                    x2="8"
                    y2="13"/>

                <line
                    x1="16"
                    y1="17"
                    x2="8"
                    y2="17"/>

            </svg>

            Log Penyewaan

        </a>


        <!-- LAPORAN ARMADA -->

        <a
            href="laporan_mobil.php"
            class="nav-item <?= ($active_menu ?? '') === 'laporan' ? 'active' : '' ?>">

            <svg
                width="16"
                height="16"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2">

                <line
                    x1="18"
                    y1="20"
                    x2="18"
                    y2="10"/>

                <line
                    x1="12"
                    y1="20"
                    x2="12"
                    y2="4"/>

                <line
                    x1="6"
                    y1="20"
                    x2="6"
                    y2="14"/>

            </svg>

            Laporan Armada

        </a>


        <!-- LAPORAN KEUANGAN -->

        <a
            href="laporan_keuangan.php"
            class="nav-item <?= ($active_menu ?? '') === 'lap_keuangan' ? 'active' : '' ?>">

            <svg
                width="16"
                height="16"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2">

                <rect
                    x="1"
                    y="4"
                    width="22"
                    height="16"
                    rx="2"/>

                <line
                    x1="1"
                    y1="10"
                    x2="23"
                    y2="10"/>

            </svg>

            Laporan Keuangan

        </a>


        <!-- KALENDER -->

        <a
            href="kalender_ketersediaan.php"
            class="nav-item <?= ($active_menu ?? '') === 'kalender' ? 'active' : '' ?>">

            <svg
                width="16"
                height="16"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2">

                <rect
                    x="3"
                    y="4"
                    width="18"
                    height="18"
                    rx="2"/>

                <line
                    x1="3"
                    y1="9"
                    x2="21"
                    y2="9"/>

                <line
                    x1="8"
                    y1="2"
                    x2="8"
                    y2="6"/>

                <line
                    x1="16"
                    y1="2"
                    x2="16"
                    y2="6"/>

            </svg>

            Kalender Sewa

        </a>


        <!-- CHECKLIST -->

        <a
            href="checklist_kendaraan.php"
            class="nav-item <?= ($active_menu ?? '') === 'checklist' ? 'active' : '' ?>">

            <svg
                width="16"
                height="16"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2">

                <path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/>

                <rect
                    x="9"
                    y="3"
                    width="6"
                    height="4"
                    rx="1"/>

                <polyline points="9 12 11 14 15 10"/>

            </svg>

            Checklist Kendaraan

        </a>


        <!-- DENDA -->

        <a
            href="denda_biaya.php"
            class="nav-item <?= ($active_menu ?? '') === 'denda' ? 'active' : '' ?>">

            <svg
                width="16"
                height="16"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2">

                <circle
                    cx="12"
                    cy="12"
                    r="10"/>

                <line
                    x1="12"
                    y1="8"
                    x2="12"
                    y2="12"/>

                <line
                    x1="12"
                    y1="16"
                    x2="12.01"
                    y2="16"/>

            </svg>

            Denda & Biaya

        </a>


        <!-- PROMO -->

        <a
            href="promo_voucher.php"
            class="nav-item <?= ($active_menu ?? '') === 'promo' ? 'active' : '' ?>">

            <svg
                width="16"
                height="16"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2">

                <path d="M20 12V22H4V12"/>

                <path d="M22 7H2v5h20V7z"/>

                <path d="M12 22V7"/>

                <path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"/>

                <path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/>

            </svg>

            Promo & Voucher

        </a>


        <!-- RATING -->

        <a
            href="rating_review.php"
            class="nav-item <?= ($active_menu ?? '') === 'review' ? 'active' : '' ?>">

            <svg
                width="16"
                height="16"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2">

                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>

            </svg>

            Rating & Review

        </a>


        <!-- NOTIFIKASI -->

        <a
            href="notifikasi.php"
            class="nav-item <?= ($active_menu ?? '') === 'notif' ? 'active' : '' ?>"
            style="justify-content:space-between">

            <span
                style="
                    display:flex;
                    align-items:center;
                    gap:var(--space-3)
                ">

                <svg
                    width="16"
                    height="16"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2">

                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>

                    <path d="M13.73 21a2 2 0 0 1-3.46 0"/>

                </svg>

                Notifikasi

            </span>


            <?php

            $unread_notif =
                db_fetch_assoc(
                    db_query(
                        $conn,
                        "SELECT COUNT(*) as c
                         FROM notifikasi_admin
                         WHERE sudah_baca=0"
                    )
                )['c'] ?? 0;

            ?>

            <span
                id="notif-badge"
                style="
                    min-width:20px;
                    height:20px;
                    padding:0 6px;
                    background:#ef4444;
                    color:#fff;
                    border-radius:999px;
                    font-size:10px;
                    font-weight:800;
                    <?= $unread_notif > 0 ? 'display:flex' : 'display:none' ?>;
                    align-items:center;
                    justify-content:center;
                    flex-shrink:0
                ">

                <?= $unread_notif ?: '' ?>

            </span>

        </a>


        <!-- CHAT -->

        <a
            href="chat.php"
            class="nav-item <?= ($active_menu ?? '') === 'chat' ? 'active' : '' ?>"
            style="justify-content:space-between">

            <span
                style="
                    display:flex;
                    align-items:center;
                    gap:var(--space-3)
                ">

                <svg
                    width="16"
                    height="16"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2">

                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>

                </svg>

                Chat Customer

            </span>


            <?php

            $chat_unread =
                db_fetch_assoc(
                    db_query(
                        $conn,
                        "SELECT COUNT(*) as c
                         FROM chat_messages
                         WHERE id_receiver IS NULL
                         AND sudah_baca=0"
                    )
                )['c'] ?? 0;

            if ($chat_unread > 0):

            ?>

            <span
                style="
                    min-width:20px;
                    height:20px;
                    padding:0 6px;
                    background:#ef4444;
                    color:#fff;
                    border-radius:999px;
                    font-size:10px;
                    font-weight:800;
                    display:flex;
                    align-items:center;
                    justify-content:center;
                    flex-shrink:0
                ">

                <?= $chat_unread ?>

            </span>

            <?php endif; ?>

        </a>


        <!-- EXPORT -->

        <a
            href="export_laporan.php?format=pdf&type=keuangan&dari=<?= date('Y-m-01') ?>&sampai=<?= date('Y-m-d') ?>"
            target="_blank"
            class="nav-item <?= ($active_menu ?? '') === 'export' ? 'active' : '' ?>">

            <svg
                width="16"
                height="16"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2">

                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>

                <polyline points="7 10 12 15 17 10"/>

                <line
                    x1="12"
                    y1="15"
                    x2="12"
                    y2="3"/>

            </svg>

            Export Laporan

        </a>


        <!-- PROFIL -->

        <a
            href="profil.php"
            class="nav-item <?= ($active_menu ?? '') === 'profil' ? 'active' : '' ?>">

            <svg
                width="16"
                height="16"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2">

                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>

                <circle
                    cx="12"
                    cy="7"
                    r="4"/>

            </svg>

            Profil Saya

        </a>


        <!-- ABOUT -->

        <a
            href="about.php"
            class="nav-item <?= ($active_menu ?? '') === 'about' ? 'active' : '' ?>">

            <svg
                width="16"
                height="16"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2">

                <circle
                    cx="12"
                    cy="12"
                    r="10"/>

                <line
                    x1="12"
                    y1="8"
                    x2="12"
                    y2="12"/>

                <line
                    x1="12"
                    y1="16"
                    x2="12.01"
                    y2="16"/>

            </svg>

            About

        </a>


        <?php else: ?>


        <!-- =================================================
             CUSTOMER MENU
        ================================================= -->

        <div class="nav-section-label">
            Menu
        </div>


        <!-- SEWA MOBIL -->

        <a
            href="pesan_mobil.php"
            class="nav-item <?= ($active_menu ?? '') === 'pesan' ? 'active' : '' ?>">

            <svg
                width="16"
                height="16"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2">

                <path d="M5 17H3a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2v3"/>

                <rect
                    x="9"
                    y="11"
                    width="14"
                    height="10"
                    rx="2"/>

            </svg>

            Sewa Mobil

        </a>


        <!-- SEWA MOTOR -->

        <a
            href="pesan_motor.php"
            class="nav-item <?= ($active_menu ?? '') === 'pesan_motor' ? 'active' : '' ?>">

            <svg
                width="16"
                height="16"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2">

                <circle
                    cx="18.5"
                    cy="17.5"
                    r="3.5"/>

                <circle
                    cx="5.5"
                    cy="17.5"
                    r="3.5"/>

                <path d="M15 6H4L2 12h14l1-6z"/>

                <path d="M15 6l2 6h4l-2-6h-4z"/>

            </svg>

            Sewa Motor

        </a>


        <!-- RIWAYAT -->

        <a
            href="riwayat_sewa.php"
            class="nav-item <?= ($active_menu ?? '') === 'riwayat' ? 'active' : '' ?>">

            <svg
                width="16"
                height="16"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2">

                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>

                <polyline points="14 2 14 8 20 8"/>

                <line
                    x1="16"
                    y1="13"
                    x2="8"
                    y2="13"/>

            </svg>

            Riwayat Sewa

        </a>


        <!-- RATING -->

        <a
            href="rating_review.php"
            class="nav-item <?= ($active_menu ?? '') === 'review' ? 'active' : '' ?>">

            <svg
                width="16"
                height="16"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2">

                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>

            </svg>

            Rating & Review

        </a>


        <!-- KALENDER -->

        <a
            href="kalender_ketersediaan.php"
            class="nav-item <?= ($active_menu ?? '') === 'kalender' ? 'active' : '' ?>">

            <svg
                width="16"
                height="16"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2">

                <rect
                    x="3"
                    y="4"
                    width="18"
                    height="18"
                    rx="2"/>

                <line
                    x1="3"
                    y1="9"
                    x2="21"
                    y2="9"/>

                <line
                    x1="8"
                    y1="2"
                    x2="8"
                    y2="6"/>

                <line
                    x1="16"
                    y1="2"
                    x2="16"
                    y2="6"/>

            </svg>

            Kalender Ketersediaan

        </a>


        <!-- CHAT ADMIN -->

        <a
            href="chat.php"
            class="nav-item <?= ($active_menu ?? '') === 'chat' ? 'active' : '' ?>"
            style="justify-content:space-between">

            <span
                style="
                    display:flex;
                    align-items:center;
                    gap:var(--space-3)
                ">

                <svg
                    width="16"
                    height="16"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2">

                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>

                </svg>

                Chat Admin

            </span>


            <?php

            $chat_unread_cust =
                db_fetch_assoc(
                    db_query(
                        $conn,
                        "SELECT COUNT(*) as c
                         FROM chat_messages
                         WHERE id_receiver='{$_SESSION['id_user']}'
                         AND sudah_baca=0"
                    )
                )['c'] ?? 0;

            if ($chat_unread_cust > 0):

            ?>

            <span
                style="
                    min-width:20px;
                    height:20px;
                    padding:0 6px;
                    background:#ef4444;
                    color:#fff;
                    border-radius:999px;
                    font-size:10px;
                    font-weight:800;
                    display:flex;
                    align-items:center;
                    justify-content:center;
                    flex-shrink:0
                ">

                <?= $chat_unread_cust ?>

            </span>

            <?php endif; ?>

        </a>


        <!-- NOTIFIKASI CUSTOMER -->

        <a
            href="notifikasi.php"
            class="nav-item <?= ($active_menu ?? '') === 'notif' ? 'active' : '' ?>"
            style="justify-content:space-between">

            <span
                style="
                    display:flex;
                    align-items:center;
                    gap:var(--space-3)
                ">

                <svg
                    width="16"
                    height="16"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2">

                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>

                    <path d="M13.73 21a2 2 0 0 1-3.46 0"/>

                </svg>

                Notifikasi

            </span>


            <?php

            $cust_notif_count =
                db_fetch_assoc(
                    db_query(
                        $conn,
                        "SELECT COUNT(*) as c
                         FROM notifikasi_customer
                         WHERE id_user='{$_SESSION['id_user']}'
                         AND sudah_baca=0"
                    )
                )['c'] ?? 0;

            if ($cust_notif_count > 0):

            ?>

            <span
                id="notif-badge"
                style="
                    min-width:20px;
                    height:20px;
                    padding:0 6px;
                    background:#ef4444;
                    color:#fff;
                    border-radius:999px;
                    font-size:10px;
                    font-weight:800;
                    display:flex;
                    align-items:center;
                    justify-content:center;
                    flex-shrink:0
                ">

                <?= $cust_notif_count ?>

            </span>

            <?php else: ?>

            <span
                id="notif-badge"
                style="
                    min-width:20px;
                    height:20px;
                    padding:0 6px;
                    background:#ef4444;
                    color:#fff;
                    border-radius:999px;
                    font-size:10px;
                    font-weight:800;
                    display:none;
                    align-items:center;
                    justify-content:center;
                    flex-shrink:0
                ">
            </span>

            <?php endif; ?>

        </a>


        <!-- PROFIL -->

        <a
            href="profil.php"
            class="nav-item <?= ($active_menu ?? '') === 'profil' ? 'active' : '' ?>">

            <svg
                width="16"
                height="16"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2">

                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>

                <circle
                    cx="12"
                    cy="7"
                    r="4"/>

            </svg>

            Profil Saya

        </a>


        <!-- ABOUT -->

        <a
            href="about.php"
            class="nav-item <?= ($active_menu ?? '') === 'about' ? 'active' : '' ?>">

            <svg
                width="16"
                height="16"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2">

                <circle
                    cx="12"
                    cy="12"
                    r="10"/>

                <line
                    x1="12"
                    y1="8"
                    x2="12"
                    y2="12"/>

                <line
                    x1="12"
                    y1="16"
                    x2="12.01"
                    y2="16"/>

            </svg>

            About

        </a>


        <?php endif; ?>

    </nav>


    <!-- =================================================
         SIDEBAR FOOTER
    ================================================= -->

    <div class="sidebar-footer">

        <div class="user-info">

            <div class="user-avatar">

                <?= strtoupper(
                    substr(
                        $_SESSION['nama_lengkap'] ?? 'U',
                        0,
                        1
                    )
                ) ?>

            </div>

            <div>

                <div class="user-name">

                    <?= htmlspecialchars(
                        $_SESSION['nama_lengkap'] ?? ''
                    ) ?>

                </div>

                <div class="user-role">

                    <?= htmlspecialchars(
                        $_SESSION['role'] ?? ''
                    ) ?>

                </div>

            </div>

        </div>


        <!-- LOGOUT -->

        <a
            href="logout.php"
            class="logout-btn">

            <svg
                width="16"
                height="16"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2">

                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>

                <polyline points="16 17 21 12 16 7"/>

                <line
                    x1="21"
                    y1="12"
                    x2="9"
                    y2="12"/>

            </svg>

            Keluar

        </a>

    </div>

</aside>


<!-- =====================================================
     HEADER
===================================================== -->

<header class="header">

    <div class="header-left">

        <button
            class="menu-toggle"
            onclick="toggleSidebar()"
            aria-label="Toggle menu">

            <svg
                width="20"
                height="20"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2">

                <line
                    x1="3"
                    y1="12"
                    x2="21"
                    y2="12"/>

                <line
                    x1="3"
                    y1="6"
                    x2="21"
                    y2="6"/>

                <line
                    x1="3"
                    y1="18"
                    x2="21"
                    y2="18"/>

            </svg>

        </button>


        <div>

            <div class="page-title">

                <?= htmlspecialchars(
                    $page_title ?? 'Dashboard'
                ) ?>

            </div>

        </div>

    </div>


    <div class="header-right">


        <?php if (
            ($_SESSION['role'] ?? '') === 'customer'
        ): ?>


        <?php

        $pending_tagihan =
            (
                db_fetch_assoc(
                    db_query(
                        $conn,
                        "SELECT COUNT(*) as c
                         FROM log_penyewaan
                         WHERE id_user='{$_SESSION['id_user']}'
                         AND payment_status='pending'
                         AND admin_confirmed=1"
                    )
                )['c'] ?? 0
            )

            +

            (
                db_fetch_assoc(
                    db_query(
                        $conn,
                        "SELECT COUNT(*) as c
                         FROM log_sewa_motor
                         WHERE id_user='{$_SESSION['id_user']}'
                         AND payment_status='pending'
                         AND admin_confirmed=1"
                    )
                )['c'] ?? 0
            );

        ?>


        <?php if ($pending_tagihan > 0): ?>

        <a
            href="riwayat_sewa.php"
            style="
                display:flex;
                align-items:center;
                gap:.5rem;
                padding:.4rem .875rem;
                background:#dbeafe;
                color:#1e40af;
                border:1px solid #bfdbfe;
                border-radius:999px;
                font-size:.75rem;
                font-weight:700;
                text-decoration:none;
                animation:pulse 2s infinite
            ">

            <svg
                width="13"
                height="13"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2.5">

                <rect
                    x="1"
                    y="4"
                    width="22"
                    height="16"
                    rx="2"/>

                <line
                    x1="1"
                    y1="10"
                    x2="23"
                    y2="10"/>

            </svg>

            <?= $pending_tagihan ?>
            Tagihan Belum Bayar

        </a>


        <style>

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: .7;
            }

        }

        </style>

        <?php endif; ?>

        <?php endif; ?>


        <!-- ONLINE STATUS -->

        <div class="header-badge">

            <div class="status-dot"></div>

            Online

        </div>

    </div>

</header>


<!-- =====================================================
     MAIN CONTENT
     Closed in _layout_footer.php
===================================================== -->

<main class="main-content">