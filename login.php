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

    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Email dan password wajib diisi.';

    } else {

        // Memeriksa akun pengguna
        $member = $userService->login($email, $password);

        if ($member !== null) {

            // Membuat session login (cegah session fixation)
            session_regenerate_id(true);

            $_SESSION['user'] = [
                'id'    => $member->getId(),
                'name'  => $member->getName(),
                'email' => $member->getEmail(),
                'role'  => $member->getRole()
            ];

            // Redirect ke halaman asal jika ada parameter redirect
            $redirect = $_GET['redirect'] ?? 'dashboard.php';

            // Validasi redirect agar tidak keluar domain (hanya path relatif)
            if (!preg_match('/^[a-zA-Z0-9_\-\.\/\#\?=]+$/', $redirect)) {
                $redirect = 'dashboard.php';
            }

            header('Location: ' . $redirect);
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Digital Library</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

    <?php require_once __DIR__ . '/templates/navbar.php'; ?>

    <main class="container">

        <section class="auth-wrapper">

            <h1>Login Member</h1>

            <p>Masuk ke akun Digital Library untuk menikmati layanan perpustakaan digital.</p>

            <?php if ($error): ?>
                <div class="alert alert-error" role="alert">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="auth-form" id="loginForm" novalidate>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input
                        type="email"
                        name="email"
                        id="email"
                        placeholder="Masukkan email"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
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
                            placeholder="Masukkan password"
                            required
                            autocomplete="current-password"
                        >
                        <button type="button" class="toggle-password" aria-label="Tampilkan/sembunyikan password" data-target="password">
                            👁
                        </button>
                    </div>
                    <span class="field-error" id="passwordError"></span>
                </div>

                <button type="submit" class="btn">Login Sekarang</button>

            </form>

            <div class="auth-link">
                Belum punya akun? <a href="register.php">Daftar di sini</a>
            </div>

        </section>

    </main>

    <?php require_once __DIR__ . '/templates/footer.php'; ?>

</body>
</html>
