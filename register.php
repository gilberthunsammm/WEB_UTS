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

$error   = '';
$success = '';
$name    = '';
$email   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name            = trim($_POST['name'] ?? '');
    $email           = trim($_POST['email'] ?? '');
    $password        = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($name === '' || $email === '' || $password === '') {
        $error = 'Semua kolom formulir wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format alamat email tidak valid.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal harus 6 karakter.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Konfirmasi password tidak sesuai.';
    } else {
        $registered = $userService->register($name, $email, $password);

        if ($registered) {
            $success = 'Pendaftaran akun berhasil! Anda kini dapat masuk ke sistem.';
            $name    = '';
            $email   = '';
        } else {
            $error = 'Email ini sudah terdaftar sebelumnya.';
        }
    }
}

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Member — Digital Library</title>
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
                    Bergabung dengan Komunitas Pembaca.
                </h2>
                <p class="auth-brand-desc">
                    Daftar akun untuk mulai meminjam e-book, membaca literatur terkini, dan melacak riwayat transaksi akademik Anda.
                </p>

                <div class="auth-brand-features">
                    <div class="auth-feature">
                        <span class="auth-feature-icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path>
                                <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>
                            </svg>
                        </span>
                        <span>Akses penuh koleksi literatur dan e-book akademik.</span>
                    </div>
                    <div class="auth-feature">
                        <span class="auth-feature-icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <line x1="18" y1="20" x2="18" y2="10"></line>
                                <line x1="12" y1="20" x2="12" y2="4"></line>
                                <line x1="6" y1="20" x2="6" y2="14"></line>
                            </svg>
                        </span>
                        <span>Dashboard monitoring aktivitas peminjaman pribadi.</span>
                    </div>
                    <div class="auth-feature">
                        <span class="auth-feature-icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                        </span>
                        <span>Pengembalian mandiri tepat waktu dalam satu klik.</span>
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

                <h1>Daftar Member</h1>
                <p>Isi data diri Anda untuk membuat akun pembaca baru.</p>

                <?php if ($error): ?>
                    <div class="alert alert-error" role="alert">
                        <span class="alert-icon">✕</span>
                        <span><?= htmlspecialchars($error) ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success" role="status">
                        <span class="alert-icon">✓</span>
                        <div>
                            <div><strong><?= htmlspecialchars($success) ?></strong></div>
                            <div style="margin-top: var(--s-3);">
                                <a href="login.php" class="btn btn-primary btn-sm">
                                    Masuk Sekarang &rarr;
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!$success): ?>
                    <form method="POST" id="registerForm" novalidate>
                        <div class="form-group">
                            <label for="name" class="form-label">Nama Lengkap</label>
                            <input
                                type="text"
                                name="name"
                                id="name"
                                class="form-input"
                                placeholder="Contoh: Budi Santoso"
                                value="<?= htmlspecialchars($name) ?>"
                                required
                                autocomplete="name"
                            >
                            <span class="field-error" id="nameError"></span>
                        </div>

                        <div class="form-group">
                            <label for="email" class="form-label">Alamat Email</label>
                            <input
                                type="email"
                                name="email"
                                id="email"
                                class="form-input"
                                placeholder="nama@email.com"
                                value="<?= htmlspecialchars($email) ?>"
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
                                    placeholder="Minimal 6 karakter"
                                    minlength="6"
                                    required
                                    autocomplete="new-password"
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

                        <div class="form-group">
                            <label for="confirm_password" class="form-label">Konfirmasi Password</label>
                            <div class="pw-wrap">
                                <input
                                    type="password"
                                    name="confirm_password"
                                    id="confirm_password"
                                    class="form-input"
                                    placeholder="Ulangi password di atas"
                                    required
                                    autocomplete="new-password"
                                >
                                <button
                                    type="button"
                                    class="pw-toggle"
                                    data-target="confirm_password"
                                    aria-label="Tampilkan atau sembunyikan konfirmasi password"
                                >
                                    👁
                                </button>
                            </div>
                            <span class="field-error" id="confirm_passwordError"></span>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg btn-full" style="margin-top: var(--s-3);">
                            Daftar Sebagai Member
                        </button>
                    </form>
                <?php endif; ?>

                <div class="auth-link-row">
                    Sudah memiliki akun? <a href="login.php">Masuk di sini</a>
                </div>
            </div>
        </main>
    </div>

    <script src="assets/js/script.js"></script>
</body>
</html>
