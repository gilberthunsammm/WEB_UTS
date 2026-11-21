<?php
/**
 * Template Navbar — Modern Editorial Library × Premium SaaS
 */
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<div class="reading-progress" id="readingProgress" aria-hidden="true"></div>

<!-- NAVBAR -->
<header class="navbar" role="banner">
    <div class="container nav-inner">
        <a href="index.php" class="nav-logo" aria-label="Digital Library Beranda">
            <span class="nav-logo-icon" aria-hidden="true">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                </svg>
            </span>
            <span>Digital Library</span>
        </a>

        <!-- Desktop Navigation -->
        <nav class="nav-links" aria-label="Navigasi Utama">
            <a href="index.php" class="nav-link <?= $currentPage === 'index.php' ? 'active' : '' ?>">
                Beranda
            </a>
            <a href="index.php#katalog" class="nav-link">
                Katalog E-Book
            </a>

            <?php if (isset($_SESSION['user'])): ?>
                <a href="dashboard.php" class="nav-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
                    Dashboard
                </a>
                <a href="my-loans.php" class="nav-link <?= $currentPage === 'my-loans.php' ? 'active' : '' ?>">
                    Peminjaman Saya
                </a>
                <?php if (($_SESSION['user']['role'] ?? '') === 'Admin'): ?>
                    <a href="manage-books.php" class="nav-link <?= $currentPage === 'manage-books.php' ? 'active' : '' ?>">
                        Kelola Buku
                    </a>
                <?php endif; ?>

                <form method="POST" action="dashboard.php" class="nav-logout-form">
                    <input type="hidden" name="logout" value="1">
                    <button type="submit" class="nav-logout-btn" onclick="return confirm('Apakah kamu yakin ingin keluar?');">
                        Keluar
                    </button>
                </form>
            <?php else: ?>
                <a href="login.php" class="nav-link <?= $currentPage === 'login.php' ? 'active' : '' ?>">
                    Masuk
                </a>
                <a href="register.php" class="btn btn-primary btn-sm <?= $currentPage === 'register.php' ? 'active' : '' ?>">
                    Daftar Akun
                </a>
            <?php endif; ?>
        </nav>

        <!-- Hamburger Toggle Mobile -->
        <button class="hamburger" id="hamburgerBtn" aria-label="Toggle menu" aria-expanded="false" aria-controls="mobileMenu">
            <span></span>
            <span></span>
            <span></span>
        </button>

        <!-- Mobile Navigation Panel -->
        <div class="nav-mobile" id="mobileMenu" aria-label="Navigasi Mobile">
            <a href="index.php" class="nav-link <?= $currentPage === 'index.php' ? 'active' : '' ?>">
                Beranda
            </a>
            <a href="index.php#katalog" class="nav-link">
                Katalog E-Book
            </a>

            <?php if (isset($_SESSION['user'])): ?>
                <a href="dashboard.php" class="nav-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
                    Dashboard
                </a>
                <a href="my-loans.php" class="nav-link <?= $currentPage === 'my-loans.php' ? 'active' : '' ?>">
                    Peminjaman Saya
                </a>
                <?php if (($_SESSION['user']['role'] ?? '') === 'Admin'): ?>
                    <a href="manage-books.php" class="nav-link <?= $currentPage === 'manage-books.php' ? 'active' : '' ?>">
                        Kelola Buku
                    </a>
                <?php endif; ?>
                <form method="POST" action="dashboard.php" class="nav-logout-form">
                    <input type="hidden" name="logout" value="1">
                    <button type="submit" class="nav-logout-btn" onclick="return confirm('Apakah kamu yakin ingin keluar?');">
                        Keluar
                    </button>
                </form>
            <?php else: ?>
                <a href="login.php" class="nav-link <?= $currentPage === 'login.php' ? 'active' : '' ?>">
                    Masuk
                </a>
                <a href="register.php" class="btn btn-primary btn-full">
                    Daftar Akun
                </a>
            <?php endif; ?>
        </div>
    </div>
</header>
