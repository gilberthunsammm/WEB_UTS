<?php

require_once __DIR__ . '/config/bootstrap.php';

use App\Services\BookService;
use App\Services\TransactionService;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$bookService = new BookService();
$transactionService = new TransactionService();

$books = $bookService->getAll();
$transactions = $transactionService->getAll();

// Menghitung data pengguna
$userFile = __DIR__ . '/data/users.json';
$users = file_exists($userFile)
    ? json_decode(file_get_contents($userFile), true)
    : [];

$totalMembers = is_array($users) ? count($users) : 0;
$totalLoans = count($transactions);

// Mengambil pesan flash notifikasi
$message     = $_SESSION['flash'] ?? '';
$messageType = $_SESSION['flash_type'] ?? '';
unset($_SESSION['flash'], $_SESSION['flash_type']);

// PROSES PEMINJAMAN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'borrow') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrfToken)) {
        $_SESSION['flash']      = 'Token keamanan tidak valid. Silakan coba kembali.';
        $_SESSION['flash_type'] = 'error';
        header('Location: index.php#katalog');
        exit;
    }

    if (!isset($_SESSION['user'])) {
        $_SESSION['flash']      = 'Silakan login terlebih dahulu untuk meminjam buku.';
        $_SESSION['flash_type'] = 'warning';
        header('Location: login.php?redirect=' . urlencode('index.php#katalog'));
        exit;
    }

    $memberId = (int) $_SESSION['user']['id'];
    $bookId   = (int) ($_POST['book_id'] ?? 0);

    if ($bookId <= 0 || $memberId <= 0) {
        $_SESSION['flash']      = 'Data peminjaman tidak valid.';
        $_SESSION['flash_type'] = 'error';
    } else {
        $success = $transactionService->borrowBook($memberId, $bookId);

        if ($success) {
            $_SESSION['flash']      = 'Buku berhasil dipinjam! Silakan periksa daftar peminjaman Anda.';
            $_SESSION['flash_type'] = 'success';
        } else {
            $_SESSION['flash']      = 'Peminjaman gagal. Buku sedang tidak tersedia atau sudah dipinjam.';
            $_SESSION['flash_type'] = 'error';
        }
    }

    header('Location: index.php#katalog');
    exit;
}

// Mengambil daftar kategori unik untuk Filter Chips
$categories = [];
foreach ($books as $b) {
    $cat = trim($b->getCategory());
    if ($cat !== '' && !in_array($cat, $categories, true)) {
        $categories[] = $cat;
    }
}
sort($categories);

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
    <title>Digital Library — Koleksi & Peminjaman E-Book Modern</title>
    <meta name="description" content="Platform e-book library modern dengan desain minimalis editorial dan katalog terkurasi.">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body class="page-wrapper">

    <?php require_once __DIR__ . '/templates/navbar.php'; ?>

    <main id="mainContent">

        <!-- HERO SECTION -->
        <section class="hero-section" aria-labelledby="heroHeading">
            <div class="container hero-inner">
                <div>
                    <span class="hero-eyebrow">
                        Katalog Terkurasi &bull; Perpustakaan Digital
                    </span>
                    <h1 class="hero-heading" id="heroHeading">
                        Jelajahi Wawasan Baru Lewat <em>Koleksi Terkurasi.</em>
                    </h1>
                    <p class="hero-desc">
                        Temukan intisari pemikiran terbaik dunia melalui kurasi buku non-fiksi terlaris, sains, psikologi, dan teknologi. Terhubung langsung dengan jutaan literatur global untuk menunjang pertumbuhan wawasan Anda.
                    </p>
                    <div class="hero-actions">
                        <a href="#katalog" class="btn btn-primary btn-lg">
                            Mulai Eksplorasi Katalog
                        </a>
                        <?php if (!isset($_SESSION['user'])): ?>
                            <a href="register.php" class="hero-link">
                                Buat Akun Baru &rarr;
                            </a>
                        <?php else: ?>
                            <a href="dashboard.php" class="hero-link">
                                Buka Dashboard Saya &rarr;
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Hero Visual Stack -->
                <div class="hero-visual" aria-hidden="true">
                    <div class="hero-mini-book">
                        <span class="hero-mini-book-title">Best Seller</span>
                    </div>
                    <div class="hero-mini-book">
                        <span class="hero-mini-book-title">Mind & Money</span>
                    </div>
                    <div class="hero-mini-book">
                        <span class="hero-mini-book-title">Tech & Ideas</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- STATISTIK SECTION -->
        <section class="stats-section" aria-label="Statistik Perpustakaan">
            <div class="container">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon-wrap" aria-hidden="true">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                                <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                            </svg>
                        </div>
                        <div>
                            <div class="stat-num" data-count="<?= count($books) ?>"><?= count($books) ?></div>
                            <div class="stat-label">Total Koleksi E-Book</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon-wrap" aria-hidden="true">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                            </svg>
                        </div>
                        <div>
                            <div class="stat-num" data-count="<?= $totalMembers ?>"><?= $totalMembers ?></div>
                            <div class="stat-label">Member Terdaftar</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon-wrap" aria-hidden="true">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="9 11 12 14 22 4"></polyline>
                                <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                            </svg>
                        </div>
                        <div>
                            <div class="stat-num" data-count="<?= $totalLoans ?>"><?= $totalLoans ?></div>
                            <div class="stat-label">Transaksi Peminjaman</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- KATALOG SECTION -->
        <section class="catalog-section" id="katalog" aria-labelledby="catalogHeading">
            <div class="container">

                <!-- Alert Feedback -->
                <?php if ($message !== ''): ?>
                    <div class="alert alert-<?= htmlspecialchars($messageType) ?>" data-auto-dismiss role="status">
                        <span class="alert-icon">
                            <?= $messageType === 'success' ? '✓' : ($messageType === 'warning' ? '⚠' : '✕') ?>
                        </span>
                        <span><?= htmlspecialchars($message) ?></span>
                    </div>
                <?php endif; ?>

                <div class="catalog-header">
                    <div>
                        <div class="section-eyebrow">Koleksi Terpilih</div>
                        <h2 class="section-heading serif" id="catalogHeading">Katalog E-Book</h2>
                        <p class="section-sub">Pilih dan pinjam e-book non-fiksi best seller pilihan editor untuk memperkaya wawasan Anda.</p>
                    </div>
                </div>

                <!-- Search Input Bar -->
                <div class="search-wrapper">
                    <svg class="search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <input
                        type="text"
                        id="searchInput"
                        class="search-input"
                        placeholder="Cari berdasarkan judul buku, nama penulis, atau kategori..."
                        aria-label="Pencarian e-book"
                    >
                </div>

                <!-- Filter Chips -->
                <?php if (!empty($categories)): ?>
                    <div class="filter-row" id="filterRow" role="group" aria-label="Filter kategori buku">
                        <button type="button" class="filter-chip active" data-filter="all">Semua Kategori</button>
                        <?php foreach ($categories as $cat): ?>
                            <button type="button" class="filter-chip" data-filter="<?= htmlspecialchars(strtolower($cat)) ?>">
                                <?= htmlspecialchars($cat) ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Empty State Search -->
                <div class="empty-state" id="emptySearch" style="display: none;" role="status">
                    <span class="empty-state-icon" aria-hidden="true">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                            <line x1="8" y1="11" x2="14" y2="11"></line>
                        </svg>
                    </span>
                    <h3>E-Book Tidak Ditemukan</h3>
                    <p>Tidak ada buku yang sesuai dengan pencarian atau filter yang dipilih.</p>
                </div>

                <!-- Book Grid -->
                <div class="book-grid" id="bookGrid">
                    <?php if (empty($books)): ?>
                        <div class="empty-state" style="grid-column: 1 / -1;">
                            <span class="empty-state-icon" aria-hidden="true">
                                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                                </svg>
                            </span>
                            <h3>Belum Ada Koleksi</h3>
                            <p>Saat ini katalog e-book masih dalam proses pembaharuan pustaka.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($books as $index => $book): ?>
                            <?php
                            $paletteNum   = ($index % 5) + 1;
                            $paletteClass = 'palette-' . $paletteNum;
                            $isAvailable  = $book->getStatus() === 'Tersedia';
                            $titleLower   = strtolower($book->getTitle());
                            $authorLower  = strtolower($book->getAuthor());
                            $catLower     = strtolower($book->getCategory());
                            $hasCover     = !empty($book->getCoverImage());
                            ?>
                            <article
                                class="book-card"
                                data-title="<?= htmlspecialchars($titleLower) ?>"
                                data-author="<?= htmlspecialchars($authorLower) ?>"
                                data-category="<?= htmlspecialchars($catLower) ?>"
                            >
                                <div class="book-cover-wrap">
                                    <?php if ($hasCover): ?>
                                        <img
                                            src="<?= htmlspecialchars($book->getCoverImage()) ?>"
                                            alt="Cover buku <?= htmlspecialchars($book->getTitle()) ?>"
                                            class="book-cover-img"
                                            loading="lazy"
                                        >
                                    <?php else: ?>
                                        <div class="book-cover-art <?= $paletteClass ?>">
                                            <span class="cover-eyebrow"><?= htmlspecialchars($book->getCategory()) ?></span>
                                            <div class="cover-title-art">
                                                <?= htmlspecialchars($book->getTitle()) ?>
                                            </div>
                                            <div class="cover-author-art">
                                                <?= htmlspecialchars($book->getAuthor()) ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Quick Action Overlay -->
                                    <div class="book-overlay">
                                        <button
                                            type="button"
                                            class="btn-quick-view"
                                            data-book-id="<?= $book->getId() ?>"
                                            data-title="<?= htmlspecialchars($book->getTitle()) ?>"
                                            data-author="<?= htmlspecialchars($book->getAuthor()) ?>"
                                            data-category="<?= htmlspecialchars($book->getCategory()) ?>"
                                            data-status="<?= htmlspecialchars($book->getStatus()) ?>"
                                            data-palette="<?= $paletteNum ?>"
                                            data-cover="<?= htmlspecialchars($book->getCoverImage()) ?>"
                                            data-description="<?= htmlspecialchars($book->getDescription()) ?>"
                                            data-publisher="<?= htmlspecialchars($book->getPublisher()) ?>"
                                            data-year="<?= htmlspecialchars($book->getPublishedYear()) ?>"
                                            data-pages="<?= $book->getPageCount() ?>"
                                            data-isbn="<?= htmlspecialchars($book->getIsbn()) ?>"
                                            aria-label="Lihat detail buku <?= htmlspecialchars($book->getTitle()) ?>"
                                        >
                                            Lihat Detail &rarr;
                                        </button>
                                    </div>
                                </div>

                                <div class="book-info">
                                    <span class="book-cat-pill">
                                        <?= htmlspecialchars($book->getCategory()) ?>
                                    </span>
                                    <h3 class="book-title-text" title="<?= htmlspecialchars($book->getTitle()) ?>">
                                        <?= htmlspecialchars($book->getTitle()) ?>
                                    </h3>
                                    <p class="book-author-text">
                                        Oleh: <?= htmlspecialchars($book->getAuthor()) ?>
                                    </p>

                                    <div class="book-card-footer">
                                        <span class="badge <?= $isAvailable ? 'badge-available' : 'badge-borrowed' ?>">
                                            <?= $isAvailable ? 'Tersedia' : 'Dipinjam' ?>
                                        </span>

                                        <form method="POST" action="index.php#katalog">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                            <input type="hidden" name="action" value="borrow">
                                            <input type="hidden" name="book_id" value="<?= $book->getId() ?>">
                                            <button
                                                type="submit"
                                                class="btn-borrow"
                                                <?= !$isAvailable ? 'disabled' : '' ?>
                                                aria-label="<?= $isAvailable ? 'Pinjam buku ' . htmlspecialchars($book->getTitle()) : 'Buku tidak tersedia' ?>"
                                            >
                                                <?= $isAvailable ? 'Pinjam' : 'Tidak Tersedia' ?>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

            </div>
        </section>

    </main>

    <!-- SLIDE-OVER QUICK VIEW DRAWER -->
    <div class="drawer-backdrop" id="drawerBackdrop" aria-hidden="true"></div>
    <aside class="drawer" id="bookDrawer" aria-labelledby="drawerTitle" aria-hidden="true" role="dialog" aria-modal="true">
        <div class="drawer-header">
            <span class="drawer-title" id="drawerTitle">Detail E-Book</span>
            <button type="button" class="drawer-close" id="drawerClose" aria-label="Tutup panel preview">✕</button>
        </div>
        <div class="drawer-body">
            <img src="" alt="" id="dImgCover" class="drawer-cover-img" style="display: none;">
            <div class="drawer-cover-art palette-1" id="dCover">
                <span class="cover-eyebrow" id="dCoverEyebrow">Kategori</span>
                <div class="cover-title-art" id="dCoverTitle">Judul Buku</div>
                <div class="cover-author-art" id="dCoverAuthor">Penulis</div>
            </div>

            <div class="drawer-meta">
                <div class="drawer-meta-row">
                    <span class="drawer-meta-label">Judul</span>
                    <span class="drawer-meta-value" id="dTitle">-</span>
                </div>
                <div class="drawer-meta-row">
                    <span class="drawer-meta-label">Penulis</span>
                    <span class="drawer-meta-value" id="dAuthor">-</span>
                </div>
                <div class="drawer-meta-row">
                    <span class="drawer-meta-label">Kategori</span>
                    <span class="drawer-meta-value" id="dCategory">-</span>
                </div>
                <div class="drawer-meta-row" id="dPublisherRow" style="display: none;">
                    <span class="drawer-meta-label">Penerbit</span>
                    <span class="drawer-meta-value" id="dPublisher">-</span>
                </div>
                <div class="drawer-meta-row" id="dYearRow" style="display: none;">
                    <span class="drawer-meta-label">Tahun</span>
                    <span class="drawer-meta-value" id="dYear">-</span>
                </div>
                <div class="drawer-meta-row" id="dPagesRow" style="display: none;">
                    <span class="drawer-meta-label">Halaman</span>
                    <span class="drawer-meta-value" id="dPages">-</span>
                </div>
                <div class="drawer-meta-row" id="dIsbnRow" style="display: none;">
                    <span class="drawer-meta-label">ISBN</span>
                    <span class="drawer-meta-value" id="dIsbn">-</span>
                </div>
                <div class="drawer-meta-row">
                    <span class="drawer-meta-label">Ketersediaan</span>
                    <div id="dStatus">-</div>
                </div>
            </div>

            <div id="dDescWrap" style="margin-bottom: var(--s-6); display: none;">
                <div style="font-size: var(--text-xs); font-weight: 700; text-transform: uppercase; letter-spacing: .7px; color: var(--text-3); margin-bottom: var(--s-2);">
                    Sinopsis E-Book
                </div>
                <div id="dDesc" style="font-size: var(--text-sm); color: var(--text-2); line-height: 1.6; max-height: 160px; overflow-y: auto; padding-right: 4px;">
                    -
                </div>
            </div>

            <div class="drawer-actions" id="dActions">
                <!-- Injected via script.js -->
            </div>
        </div>
    </aside>

    <?php require_once __DIR__ . '/templates/footer.php'; ?>

</body>
</html>
