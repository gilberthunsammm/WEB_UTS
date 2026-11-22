<?php

require_once __DIR__ . '/config/bootstrap.php';

use App\Services\UserService;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user'])) {
    header('Location: dashboard.php');
    exit;
}

$userService = new UserService();
$error = '';

$flashMessage = $_SESSION['flash'] ?? '';
$flashType    = $_SESSION['flash_type'] ?? 'info';
unset($_SESSION['flash'], $_SESSION['flash_type']);

if (isset($_GET['status']) && $_GET['status'] === 'logged_out') {
    $flashMessage = 'Anda telah berhasil keluar dari akun.';
    $flashType    = 'success';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Email dan password wajib diisi.';
    } else {
        $member = $userService->login($email, $password);

        if ($member !== null) {
            session_regenerate_id(true);

            $_SESSION['user'] = [
                'id'    => $member->getId(),
                'name'  => $member->getName(),
                'email' => $member->getEmail(),
                'role'  => $member->getRole()
            ];

            $redirect = $_GET['redirect'] ?? 'dashboard.php';
            if (!preg_match('/^[a-zA-Z0-9_\-\.\/\#\?=]+$/', $redirect)) {
                $redirect = 'dashboard.php';
            }

            header('Location: ' . $redirect);
            exit;
        } else {
            $error = 'Email atau password yang Anda masukkan salah.';
        }
    }
}

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk — Digital Library</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

    <div class="auth-layout">
        <!-- Brand Visual Side -->
        <aside class="auth-brand" aria-label="Informasi Digital Library">
            <a href="index.php" class="auth-brand-logo">
                <span class="auth-brand-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                    </svg>
                </span>
                <span>Digital Library</span>
            </a>

            <div class="auth-brand-content">
                <h2 class="auth-brand-heading">
                    Pusat Koleksi Buku Digital Terlengkap.
                </h2>
                <p class="auth-brand-desc">
                    Masuk ke portal akademik untuk mengelola peminjaman e-book, mengakses bahan ajar, dan memantau status peminjaman secara instan.
                </p>

                <div class="auth-brand-features">
                    <div class="auth-feature">
                        <span class="auth-feature-icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                        </span>
                        <span>Peminjaman mandiri cepat dan tanpa antrean fisik.</span>
                    </div>
                    <div class="auth-feature">
                        <span class="auth-feature-icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                            </svg>
                        </span>
                        <span>Akun aman terintegrasi sesi terenkripsi server-side.</span>
                    </div>
                    <div class="auth-feature">
                        <span class="auth-feature-icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                                <line x1="8" y1="21" x2="16" y2="21"></line>
                                <line x1="12" y1="17" x2="12" y2="21"></line>
                            </svg>
                        </span>
                        <span>Akses fleksibel lintas desktop, tablet, dan smartphone.</span>
                    </div>
                </div>
            </div>

            <div class="auth-brand-footer">
                &copy; <?= date('Y') ?> Digital Library System.
            </div>
        </aside>

        <!-- Form Side -->
        <main class="auth-form-panel">
            <div class="auth-form-box">
                <div style="margin-bottom: var(--s-6);">
                    <a href="index.php" class="btn-link" style="padding-left: 0;">
                        &larr; Kembali ke Beranda
                    </a>
                </div>

                <h1>Masuk Akun</h1>
                <p>Masukkan kredensial akun Anda untuk melanjutkan akses.</p>

                <?php if ($flashMessage): ?>
                    <div class="alert alert-<?= htmlspecialchars($flashType) ?>" role="alert" style="margin-bottom: var(--s-4);">
                        <span><?= htmlspecialchars($flashMessage) ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-error" role="alert">
                        <span class="alert-icon">✕</span>
                        <span><?= htmlspecialchars($error) ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" id="loginForm" novalidate>
                    <div class="form-group">
                        <label for="email" class="form-label">Alamat Email</label>
                        <input
                            type="email"
                            name="email"
                            id="email"
                            class="form-input"
                            placeholder="nama@email.com"
                            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                            required
                            autocomplete="email"
                        >
                        <span class="field-error" id="emailError"></span>
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <div class="pw-wrap">
                            <input
                                type="password"
                                name="password"
                                id="password"
                                class="form-input"
                                placeholder="Masukkan password Anda"
                                required
                                autocomplete="current-password"
                            >
                            <button
                                type="button"
                                class="pw-toggle"
                                data-target="password"
                                aria-label="Tampilkan atau sembunyikan password"
                            >
                                👁
                            </button>
                        </div>
                        <span class="field-error" id="passwordError"></span>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg btn-full" style="margin-top: var(--s-3);">
                        Masuk ke Dashboard
                    </button>
                </form>

                <div class="auth-link-row">
                    Belum memiliki akun? <a href="register.php">Daftar sekarang</a>
                </div>
            </div>
        </main>
    </div>

    <script src="assets/js/script.js"></script>
</body>
</html>
