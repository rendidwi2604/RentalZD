<?php

require_once __DIR__ . '/config/bootstrap.php';

require_once 'koneksi.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {

        $stmt = $conn->prepare("SELECT * FROM app_users WHERE username = ?");
        $stmt->execute([$username]);

        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);

            $_SESSION['id_user']      = $user['id_user'];
            $_SESSION['username']     = $user['username'];
            $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
            $_SESSION['role']         = $user['role'];

            if ($user['role'] === 'admin') {
                header('Location: rentalmobil.php');
            } else {
                header('Location: pesan_mobil.php');
            }

            exit;

        } else {
            $error = 'Username atau password salah.';
        }

    } else {
        $error = 'Harap isi semua field.';
    }
}

?>

<!DOCTYPE html>
<html lang="id" data-theme="light">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Login — ZD RENT</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>

/* =========================================================
   DESIGN TOKENS
========================================================= */

:root {

    --font-body: 'Plus Jakarta Sans', sans-serif;

    --text-xs: clamp(0.75rem, 0.7rem + 0.25vw, 0.875rem);
    --text-sm: clamp(0.875rem, 0.8rem + 0.35vw, 1rem);
    --text-base: clamp(1rem, 0.95rem + 0.25vw, 1.125rem);
    --text-lg: clamp(1.125rem, 1rem + 0.75vw, 1.5rem);
    --text-xl: clamp(1.5rem, 1.2rem + 1.25vw, 2.25rem);
    --text-2xl: clamp(2rem, 1.2rem + 2.5vw, 3.5rem);

    --space-1: 0.25rem;
    --space-2: 0.5rem;
    --space-3: 0.75rem;
    --space-4: 1rem;
    --space-5: 1.25rem;
    --space-6: 1.5rem;
    --space-8: 2rem;
    --space-10: 2.5rem;
    --space-12: 3rem;
    --space-16: 4rem;

    /* BLUE WHITE THEME */

    --color-bg: #ffffff;

    --color-surface: #f8fbff;
    --color-surface-2: #eef5ff;

    --color-border: rgba(37, 99, 235, 0.15);
    --color-divider: rgba(37, 99, 235, 0.08);

    --color-text: #172033;
    --color-text-muted: #526070;
    --color-text-faint: #8a96a6;

    --color-text-inverse: #ffffff;

    --color-primary: #1d4ed8;
    --color-primary-hover: #1e40af;

    --color-primary-highlight: rgba(37, 99, 235, 0.08);

    --color-accent: #2563eb;
    --color-accent-hover: #1d4ed8;

    --color-success: #16805c;

    --color-error: #c1121f;
    --color-error-bg: #fff1f0;

    --radius-sm: 0.375rem;
    --radius-md: 0.5rem;
    --radius-lg: 0.75rem;
    --radius-xl: 1rem;
    --radius-2xl: 1.5rem;

    --radius-full: 9999px;

    --shadow-sm:
        0 1px 3px rgba(15, 63, 174, 0.08),
        0 1px 2px rgba(15, 63, 174, 0.04);

    --shadow-md:
        0 4px 12px rgba(15, 63, 174, 0.10),
        0 2px 4px rgba(15, 63, 174, 0.05);

    --shadow-lg:
        0 20px 40px rgba(15, 63, 174, 0.14),
        0 8px 16px rgba(15, 63, 174, 0.07);

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

    min-height: 100dvh;

    font-family: var(--font-body);
    font-size: var(--text-base);

    color: var(--color-text);
    background: var(--color-bg);

    display: flex;
    align-items: stretch;
}

img {
    display: block;
    max-width: 100%;
}

input,
button {
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


/* =========================================================
   LAYOUT
========================================================= */

.page-wrapper {

    display: flex;

    width: 100%;
    min-height: 100dvh;
}


/* =========================================================
   LEFT PANEL
========================================================= */

.left-panel {

    flex: 0 0 55%;

    background:
        linear-gradient(
            135deg,
            #0f3fae 0%,
            #1d4ed8 50%,
            #2563eb 100%
        );

    display: flex;

    flex-direction: column;
    justify-content: space-between;

    padding: var(--space-10) var(--space-12);

    position: relative;

    overflow: hidden;
}


/* Dekorasi kanan atas */

.left-panel::before {

    content: '';

    position: absolute;

    top: -160px;
    right: -160px;

    width: 560px;
    height: 560px;

    background:
        radial-gradient(
            circle,
            rgba(255,255,255,0.16) 0%,
            rgba(255,255,255,0.04) 35%,
            transparent 70%
        );

    pointer-events: none;
}


/* Dekorasi kiri bawah */

.left-panel::after {

    content: '';

    position: absolute;

    bottom: -140px;
    left: -120px;

    width: 480px;
    height: 480px;

    background:
        radial-gradient(
            circle,
            rgba(255,255,255,0.12) 0%,
            transparent 68%
        );

    pointer-events: none;
}


/* =========================================================
   BRAND
========================================================= */

.brand {

    display: flex;

    align-items: center;

    gap: var(--space-3);

    z-index: 1;
}

.brand-logo {

    width: 44px;
    height: 44px;

    background: rgba(255,255,255,0.16);

    border: 1px solid rgba(255,255,255,0.22);

    border-radius: var(--radius-md);

    display: flex;
    align-items: center;
    justify-content: center;

    flex-shrink: 0;

    box-shadow:
        0 8px 20px rgba(0,0,0,0.12);

    backdrop-filter: blur(8px);
}

.brand-logo svg {
    color: white;
}

.brand-name {

    font-size: var(--text-lg);

    font-weight: 800;

    color: #ffffff;

    letter-spacing: -0.02em;
}

.brand-tagline {

    font-size: var(--text-xs);

    color: rgba(255,255,255,0.62);

    text-transform: uppercase;

    letter-spacing: 0.1em;

    margin-top: 1px;
}


/* =========================================================
   HERO CONTENT
========================================================= */

.hero-content {

    z-index: 1;
}

.hero-content h1 {

    font-size: var(--text-2xl);

    font-weight: 800;

    color: #ffffff;

    line-height: 1.1;

    letter-spacing: -0.03em;

    margin-bottom: var(--space-6);
}

.hero-content h1 span {

    color: #bfdbfe;
}

.hero-content p {

    font-size: var(--text-base);

    color: rgba(255,255,255,0.76);

    line-height: 1.7;

    max-width: 44ch;
}


/* =========================================================
   FEATURE LIST
========================================================= */

.feature-list {

    display: flex;

    flex-direction: column;

    gap: var(--space-4);

    margin-top: var(--space-8);
}

.feature-item {

    display: flex;

    align-items: center;

    gap: var(--space-3);

    color: rgba(255,255,255,0.84);

    font-size: var(--text-sm);
}

.feature-dot {

    width: 8px;
    height: 8px;

    border-radius: 50%;

    background: #ffffff;

    box-shadow:
        0 0 0 4px rgba(255,255,255,0.12);

    flex-shrink: 0;
}


/* =========================================================
   LEFT FOOTER
========================================================= */

.left-footer {

    z-index: 1;

    font-size: var(--text-xs);

    color: rgba(255,255,255,0.55);
}


/* =========================================================
   RIGHT PANEL
========================================================= */

.right-panel {

    flex: 1;

    display: flex;

    align-items: center;

    justify-content: center;

    padding: var(--space-8) var(--space-6);

    background: #ffffff;
}

.login-box {

    width: 100%;

    max-width: 400px;
}


/* =========================================================
   LOGIN HEADER
========================================================= */

.login-box-header {

    margin-bottom: var(--space-8);
}

.login-box-header h2 {

    font-size: var(--text-xl);

    font-weight: 800;

    color: var(--color-text);

    letter-spacing: -0.02em;

    margin-bottom: var(--space-2);
}

.login-box-header p {

    font-size: var(--text-sm);

    color: var(--color-text-muted);
}


/* =========================================================
   FORM
========================================================= */

.form-group {

    margin-bottom: var(--space-5);
}

.form-label {

    display: block;

    font-size: var(--text-sm);

    font-weight: 600;

    color: var(--color-text);

    margin-bottom: var(--space-2);
}

.input-wrapper {

    position: relative;
}

.input-icon {

    position: absolute;

    left: var(--space-4);

    top: 50%;

    transform: translateY(-50%);

    color: var(--color-text-faint);

    pointer-events: none;

    display: flex;

    align-items: center;

    z-index: 1;
}

.form-input {

    width: 100%;

    padding:
        var(--space-3)
        var(--space-4)
        var(--space-3)
        calc(var(--space-4) + 28px);

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

.form-input:focus {

    outline: none;

    border-color: var(--color-primary);

    background: #ffffff;

    box-shadow:
        0 0 0 3px rgba(37,99,235,0.10);
}

.form-input::placeholder {

    color: var(--color-text-faint);
}


/* =========================================================
   PASSWORD TOGGLE
========================================================= */

.toggle-password {

    position: absolute;

    right: var(--space-4);

    top: 50%;

    transform: translateY(-50%);

    color: var(--color-text-faint);

    display: flex;

    align-items: center;

    transition:
        color var(--transition);
}

.toggle-password:hover {

    color: var(--color-primary);
}


/* =========================================================
   ERROR
========================================================= */

.alert-error {

    display: flex;

    align-items: center;

    gap: var(--space-3);

    padding:
        var(--space-3)
        var(--space-4);

    background: var(--color-error-bg);

    border:
        1px solid
        rgba(193,18,31,0.15);

    border-radius: var(--radius-lg);

    color: var(--color-error);

    font-size: var(--text-sm);

    margin-bottom: var(--space-5);
}


/* =========================================================
   LOGIN BUTTON
========================================================= */

.btn-primary {

    width: 100%;

    padding:
        var(--space-4)
        var(--space-6);

    background:
        linear-gradient(
            135deg,
            #1d4ed8,
            #2563eb
        );

    color: var(--color-text-inverse);

    border-radius: var(--radius-lg);

    font-size: var(--text-sm);

    font-weight: 700;

    letter-spacing: 0.01em;

    border: none;

    cursor: pointer;

    transition:
        background var(--transition),
        transform var(--transition),
        box-shadow var(--transition);

    display: flex;

    align-items: center;

    justify-content: center;

    gap: var(--space-2);

    box-shadow:
        0 8px 18px rgba(37,99,235,0.20);
}

.btn-primary:hover {

    background:
        linear-gradient(
            135deg,
            #1e40af,
            #1d4ed8
        );

    box-shadow:
        0 12px 24px rgba(37,99,235,0.28);

    transform: translateY(-1px);
}

.btn-primary:active {

    transform: translateY(0);
}


/* =========================================================
   FOOTER
========================================================= */

.login-footer {

    margin-top: var(--space-6);

    text-align: center;

    font-size: var(--text-xs);

    color: var(--color-text-faint);
}


/* =========================================================
   MOBILE
========================================================= */

@media (max-width: 768px) {

    .left-panel {
        display: none;
    }

    .right-panel {

        flex-direction: column;

        justify-content: center;

        padding:
            var(--space-6)
            var(--space-5);

        background:
            linear-gradient(
                180deg,
                #f8fbff 0%,
                #ffffff 100%
            );
    }

    .mobile-brand {

        display: flex !important;

        align-items: center;

        gap: var(--space-3);

        margin-bottom: var(--space-8);
    }

    .mobile-brand-logo {

        width: 40px;
        height: 40px;

        background:
            linear-gradient(
                135deg,
                #1d4ed8,
                #2563eb
            );

        border-radius: var(--radius-md);

        display: flex;

        align-items: center;

        justify-content: center;

        box-shadow:
            0 6px 14px rgba(37,99,235,0.20);
    }

    .mobile-brand-name {

        font-size: var(--text-lg);

        font-weight: 800;

        color: var(--color-text);

        letter-spacing: -0.02em;
    }
}

.mobile-brand {
    display: none;
}

</style>

</head>


<body>

<div class="page-wrapper">


    <!-- =====================================================
         LEFT PANEL
    ====================================================== -->

    <div class="left-panel">

        <div class="brand">

            <div class="brand-logo">

                <svg
                    width="24"
                    height="24"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="white"
                    stroke-width="2.5"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >

                    <path d="M5 17H3a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2v3"/>

                    <rect
                        x="9"
                        y="11"
                        width="14"
                        height="10"
                        rx="2"
                    />

                    <circle
                        cx="12"
                        cy="16"
                        r="1"
                    />

                    <circle
                        cx="20"
                        cy="16"
                        r="1"
                    />

                </svg>

            </div>


            <div>

                <div class="brand-name">
                    ZD RENT
                </div>

                <div class="brand-tagline">
                    Premium Car Rental
                </div>

            </div>

        </div>


        <!-- HERO -->

        <div class="hero-content">

            <h1>
                Sewa Mobil<br>
                <span>Mudah</span> &amp;<br>
                Terpercaya
            </h1>

            <p>
                Armada kendaraan pilihan siap mengantarmu ke mana saja.
                Proses cepat, harga transparan.
            </p>


            <div class="feature-list">

                <div class="feature-item">

                    <div class="feature-dot"></div>

                    Armada lengkap dari berbagai merek

                </div>


                <div class="feature-item">

                    <div class="feature-dot"></div>

                    Harga transparan tanpa biaya tersembunyi

                </div>


                <div class="feature-item">

                    <div class="feature-dot"></div>

                    Proses pemesanan cepat &amp; mudah

                </div>


                <div class="feature-item">

                    <div class="feature-dot"></div>

                    Layanan pelanggan 24/7

                </div>

            </div>

        </div>


        <div class="left-footer">

            © 2026 ZD RENT. All rights reserved.

        </div>

    </div>


    <!-- =====================================================
         RIGHT PANEL
    ====================================================== -->

    <div class="right-panel">

        <div class="login-box">


            <!-- MOBILE BRAND -->

            <div class="mobile-brand">

                <div class="mobile-brand-logo">

                    <svg
                        width="22"
                        height="22"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="white"
                        stroke-width="2.5"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >

                        <path d="M5 17H3a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2v3"/>

                        <rect
                            x="9"
                            y="11"
                            width="14"
                            height="10"
                            rx="2"
                        />

                        <circle
                            cx="12"
                            cy="16"
                            r="1"
                        />

                        <circle
                            cx="20"
                            cy="16"
                            r="1"
                        />

                    </svg>

                </div>


                <div class="mobile-brand-name">
                    ZD RENT
                </div>

            </div>


            <!-- LOGIN HEADER -->

            <div class="login-box-header">

                <h2>
                    Masuk ke Akun
                </h2>

                <p>
                    Masukkan kredensial Anda untuk melanjutkan
                </p>

            </div>


            <!-- ERROR -->

            <?php if ($error): ?>

                <div class="alert-error">

                    <svg
                        width="16"
                        height="16"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >

                        <circle
                            cx="12"
                            cy="12"
                            r="10"
                        />

                        <line
                            x1="12"
                            y1="8"
                            x2="12"
                            y2="12"
                        />

                        <line
                            x1="12"
                            y1="16"
                            x2="12.01"
                            y2="16"
                        />

                    </svg>

                    <?= htmlspecialchars($error) ?>

                </div>

            <?php endif; ?>


            <!-- LOGIN FORM -->

            <form method="POST" action="">


                <!-- USERNAME -->

                <div class="form-group">

                    <label
                        class="form-label"
                        for="username"
                    >
                        Username
                    </label>


                    <div class="input-wrapper">

                        <span class="input-icon">

                            <svg
                                width="16"
                                height="16"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >

                                <path
                                    d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"
                                />

                                <circle
                                    cx="12"
                                    cy="7"
                                    r="4"
                                />

                            </svg>

                        </span>


                        <input
                            type="text"
                            id="username"
                            name="username"
                            class="form-input"
                            placeholder="Masukkan username"
                            value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                            autocomplete="username"
                            required
                        >

                    </div>

                </div>


                <!-- PASSWORD -->

                <div class="form-group">

                    <label
                        class="form-label"
                        for="password"
                    >
                        Password
                    </label>


                    <div class="input-wrapper">

                        <span class="input-icon">

                            <svg
                                width="16"
                                height="16"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >

                                <rect
                                    x="3"
                                    y="11"
                                    width="18"
                                    height="11"
                                    rx="2"
                                />

                                <path
                                    d="M7 11V7a5 5 0 0 1 10 0v4"
                                />

                            </svg>

                        </span>


                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-input"
                            placeholder="Masukkan password"
                            autocomplete="current-password"
                            required
                        >


                        <button
                            type="button"
                            class="toggle-password"
                            onclick="togglePassword()"
                            aria-label="Toggle password visibility"
                        >

                            <svg
                                id="eye-icon"
                                width="16"
                                height="16"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >

                                <path
                                    d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"
                                />

                                <circle
                                    cx="12"
                                    cy="12"
                                    r="3"
                                />

                            </svg>

                        </button>

                    </div>

                </div>


                <!-- BUTTON -->

                <button
                    type="submit"
                    class="btn-primary"
                >

                    <svg
                        width="16"
                        height="16"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2.5"
                    >

                        <path
                            d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"
                        />

                        <polyline
                            points="10 17 15 12 10 7"
                        />

                        <line
                            x1="15"
                            y1="12"
                            x2="3"
                            y2="12"
                        />

                    </svg>

                    Masuk Sekarang

                </button>

            </form>


            <!-- FOOTER -->

            <div class="login-footer">

                © 2026 ZD RENT — Premium Car Rental Service

            </div>

        </div>

    </div>

</div>


<!-- =====================================================
     JAVASCRIPT
====================================================== -->

<script>

function togglePassword() {

    const input = document.getElementById('password');
    const icon = document.getElementById('eye-icon');

    if (input.type === 'password') {

        input.type = 'text';

        icon.innerHTML = `
            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
            <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
            <line x1="1" y1="1" x2="23" y2="23"/>
        `;

    } else {

        input.type = 'password';

        icon.innerHTML = `
            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
            <circle cx="12" cy="12" r="3"/>
        `;

    }

}

</script>

</body>

</html>