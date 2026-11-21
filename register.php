<?php

require_once __DIR__ . '/config/bootstrap.php';

use App\Services\UserService;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userService = new UserService();

$error = '';
$success = '';

$name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Validasi input
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
        $registered = $userService->register(
            $name,
            $email,
            $password
        );

        if ($registered) {
            $success = 'Registrasi berhasil! Silakan login.';
            $name = '';
            $email = '';
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

    <style>
        .register-wrapper {
            max-width: 520px;
            margin: 60px auto;
            padding: 35px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.06);
        }

        .register-wrapper h1 {
            color: #6f42c1;
            margin-bottom: 10px;
        }

        .register-wrapper p {
            color: #777;
            margin-bottom: 25px;
        }

        .register-form label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .register-form input {
            width: 100%;
            padding: 13px;
            margin-bottom: 18px;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-sizing: border-box;
        }

        .register-form button {
            width: 100%;
            border: none;
            cursor: pointer;
            padding: 14px;
        }

        .message {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .error {
            background: #ffe5e5;
            color: #c62828;
        }

        .success {
            background: #e4f7e9;
            color: #237a3b;
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
        }
    </style>
</head>

<body>

<header class="navbar">
    <div class="container nav-content">

        <div class="logo">📚 Digital Library</div>

        <nav class="nav-links">
            <a href="index.php">Beranda</a>
            <a href="index.php#katalog">Katalog</a>
            <a href="login.php">Login</a>
        </nav>

    </div>
</header>

<main class="container">

    <section class="register-wrapper">

        <h1>Daftar Member</h1>

        <p>
            Buat akun Digital Library dan nikmati
            pengalaman membaca buku digital.
        </p>

        <?php if ($error): ?>
            <div class="message error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="message success">
                <?= htmlspecialchars($success) ?>
                <br><br>
                <a href="login.php">Login sekarang</a>
            </div>
        <?php endif; ?>

        <form method="POST" class="register-form">

            <label>Nama Lengkap</label>
            <input
                type="text"
                name="name"
                placeholder="Masukkan nama lengkap"
                value="<?= htmlspecialchars($name) ?>"
                required
            >

            <label>Email</label>
            <input
                type="email"
                name="email"
                placeholder="Masukkan email"
                value="<?= htmlspecialchars($email) ?>"
                required
            >

            <label>Password</label>
            <input
                type="password"
                name="password"
                placeholder="Minimal 6 karakter"
                minlength="6"
                required
            >

            <label>Konfirmasi Password</label>
            <input
                type="password"
                name="confirm_password"
                placeholder="Ulangi password"
                required
            >

            <button type="submit" class="btn">
                Daftar Sekarang
            </button>

        </form>

        <div class="login-link">
            Sudah punya akun?
            <a href="login.php">Login di sini</a>
        </div>

    </section>

</main>

<footer>
    <p>&copy; 2026 Digital Library. All Rights Reserved.</p>
</footer>

</body>
</html>
