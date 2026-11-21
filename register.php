<?php

require_once __DIR__ . '/config/bootstrap.php';

use App\Services\UserService;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
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

    // Validasi server-side
    if ($name === '' || $email === '' || $password === '') {
        $error = 'Semua kolom wajib diisi.';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';

    } elseif (strlen($password) < 6) {
        $error = 'Password minimal harus 6 karakter.';

    } elseif ($password !== $confirmPassword) {
        $error = 'Konfirmasi password tidak cocok.';

    } else {

        // Menyimpan akun baru
        $registered = $userService->register($name, $email, $password);

        if ($registered) {
            $success = 'Registrasi berhasil!';
            $name    = '';
            $email   = '';
        } else {
            $error = 'Email sudah terdaftar atau data gagal disimpan.';
        }
    }
}

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Member - Digital Library</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

    <?php require_once __DIR__ . '/templates/navbar.php'; ?>

    <main class="container">

        <section class="auth-wrapper" style="max-width: 520px;">

            <h1>Daftar Member</h1>

            <p>Buat akun Digital Library dan nikmati pengalaman membaca buku digital.</p>

            <?php if ($error): ?>
                <div class="alert alert-error" role="alert">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success" role="status">
                    <strong>Registrasi Berhasil!</strong> Akun kamu sudah dibuat.
                    <div style="margin-top: 14px;">
                        <a href="login.php" class="btn" style="font-size: 14px;">
                            Login Sekarang →
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!$success): ?>
            <form method="POST" class="auth-form" id="registerForm" novalidate>

                <div class="form-group">
                    <label for="name">Nama Lengkap</label>
                    <input
                        type="text"
                        name="name"
                        id="name"
                        placeholder="Masukkan nama lengkap"
                        value="<?= htmlspecialchars($name) ?>"
                        required
                        autocomplete="name"
                    >
                    <span class="field-error" id="nameError"></span>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input
                        type="email"
                        name="email"
                        id="email"
                        placeholder="Masukkan email"
                        value="<?= htmlspecialchars($email) ?>"
                        required
                        autocomplete="email"
                    >
                    <span class="field-error" id="emailError"></span>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-wrapper">
                        <input
                            type="password"
                            name="password"
                            id="password"
                            placeholder="Minimal 6 karakter"
                            minlength="6"
                            required
                            autocomplete="new-password"
                        >
                        <button type="button" class="toggle-password" aria-label="Tampilkan/sembunyikan password" data-target="password">
                            👁
                        </button>
                    </div>
                    <span class="field-error" id="passwordError"></span>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Konfirmasi Password</label>
                    <div class="password-wrapper">
                        <input
                            type="password"
                            name="confirm_password"
                            id="confirm_password"
                            placeholder="Ulangi password"
                            required
                            autocomplete="new-password"
                        >
                        <button type="button" class="toggle-password" aria-label="Tampilkan/sembunyikan konfirmasi password" data-target="confirm_password">
                            👁
                        </button>
                    </div>
                    <span class="field-error" id="confirmPasswordError"></span>
                </div>

                <button type="submit" class="btn">Daftar Sekarang</button>

            </form>
            <?php endif; ?>

            <div class="auth-link">
                Sudah punya akun? <a href="login.php">Login di sini</a>
            </div>

        </section>

    </main>

    <?php require_once __DIR__ . '/templates/footer.php'; ?>

</body>
</html>
