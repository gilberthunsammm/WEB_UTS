<?php

require_once __DIR__ . '/config/bootstrap.php';

use App\Services\UserService;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Jika sudah login, arahkan ke dashboard
if (isset($_SESSION['user'])) {
    header('Location: dashboard.php');
    exit;
}

$userService = new UserService();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Email dan password wajib diisi.';

    } else {

        // Memeriksa akun pengguna
        $member = $userService->login(
            $email,
            $password
        );

        if ($member !== null) {

            // Membuat session login
            session_regenerate_id(true);

            $_SESSION['user'] = [
                'id' => $member->getId(),
                'name' => $member->getName(),
                'email' => $member->getEmail(),
                'role' => $member->getRole()
            ];

            // Menuju dashboard
            header('Location: dashboard.php');
            exit;

        } else {
            $error = 'Email atau password salah.';
        }
    }
}

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Login - Digital Library</title>

    <link rel="stylesheet"
          href="assets/css/style.css">

    <style>
        .login-wrapper {
            max-width: 480px;
            margin: 60px auto;
            padding: 35px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.06);
        }

        .login-wrapper h1 {
            color: #6f42c1;
            margin-bottom: 10px;
        }

        .login-wrapper p {
            color: #777;
            margin-bottom: 25px;
        }

        .login-form label {
            display: block;
            margin-bottom: 8px;
        }

        .login-form input {
            width: 100%;
            padding: 13px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-sizing: border-box;
        }

        .login-form button {
            width: 100%;
            padding: 14px;
            border: none;
            cursor: pointer;
        }

        .error {
            background: #ffe5e5;
            color: #c62828;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .register-link {
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
            <a href="register.php">Daftar Member</a>
        </nav>

    </div>

</header>

<main class="container">

    <section class="login-wrapper">

        <h1>Login Member</h1>

        <p>
            Masuk ke akun Digital Library untuk
            menikmati layanan perpustakaan digital.
        </p>

        <?php if ($error): ?>

            <div class="error">
                <?= htmlspecialchars($error) ?>
            </div>

        <?php endif; ?>

        <form method="POST" class="login-form">

            <label>Email</label>

            <input
                type="email"
                name="email"
                placeholder="Masukkan email"
                required
            >

            <label>Password</label>

            <input
                type="password"
                name="password"
                placeholder="Masukkan password"
                required
            >

            <button type="submit" class="btn">
                Login Sekarang
            </button>

        </form>

        <div class="register-link">

            Belum punya akun?

            <a href="register.php">
                Daftar di sini
            </a>

        </div>

    </section>

</main>

<footer>
    <p>
        &copy; 2026 Digital Library.
        All Rights Reserved.
    </p>
</footer>

</body>
</html>
