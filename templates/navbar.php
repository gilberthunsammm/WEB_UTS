<?php
/**
 * Template Navbar — Komponen yang dapat digunakan ulang di seluruh halaman.
 * Gunakan: require_once __DIR__ . '/../templates/navbar.php';
 * Pastikan session_start() sudah dipanggil sebelum include file ini.
 */

// Tentukan halaman aktif berdasarkan nama file yang sedang diakses
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!-- NAVBAR -->
<header class="navbar">

    <div class="container nav-content">

        <div class="logo">📚 Digital Library</div>

        <!-- Tombol hamburger untuk mobile -->
        <button
            class="hamburger-btn"
            id="hamburgerBtn"
            aria-label="Toggle menu navigasi"
            aria-expanded="false"
        >
            <span></span>
            <span></span>
            <span></span>
        </button>

        <nav class="nav-links" id="navLinks" aria-label="Navigasi utama">

            <a href="index.php"
               class="<?= $currentPage === 'index.php' ? 'nav-active' : '' ?>">
                Beranda
            </a>

            <a href="index.php#katalog">Katalog</a>

            <?php if (isset($_SESSION['user'])): ?>

                <a href="dashboard.php"
                   class="<?= $currentPage === 'dashboard.php' ? 'nav-active' : '' ?>">
                    Dashboard
                </a>

                <a href="my-loans.php"
                   class="<?= $currentPage === 'my-loans.php' ? 'nav-active' : '' ?>">
                    Peminjaman Saya
                </a>

                <?php if (($_SESSION['user']['role'] ?? '') === 'Admin'): ?>
                    <a href="manage-books.php"
                       class="<?= $currentPage === 'manage-books.php' ? 'nav-active' : '' ?>">
                        Kelola Buku
                    </a>
                <?php endif; ?>

                <form method="POST" action="dashboard.php" class="nav-logout-form">
                    <input type="hidden" name="logout" value="1">
                    <button type="submit" class="btn btn-logout-nav"
                            onclick="return confirm('Apakah kamu yakin ingin logout?')">
                        Logout
                    </button>
                </form>

            <?php else: ?>

                <a href="login.php"
                   class="<?= $currentPage === 'login.php' ? 'nav-active' : '' ?>">
                    Login
                </a>

                <a href="register.php" class="btn
                   <?= $currentPage === 'register.php' ? 'nav-active' : '' ?>">
                    Daftar Member
                </a>

            <?php endif; ?>

        </nav>

    </div>

</header>
