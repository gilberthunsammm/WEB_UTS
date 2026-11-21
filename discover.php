<?php

require_once __DIR__ . '/config/bootstrap.php';

use App\Services\GoogleBooksService;
use App\Services\BookService;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$googleBooksService = new GoogleBooksService();
$bookService = new BookService();

$isAdmin = (isset($_SESSION['user']) && (($_SESSION['user']['role'] ?? '') === 'Admin'));

// 1. PROSES SIMPAN / UPDATE API KEY GOOGLE BOOKS
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['action'] ?? '') === 'save_api_key') {
    if (!isset($_SESSION['user']) || (($_SESSION['user']['role'] ?? '') !== 'Admin')) {
        $_SESSION['flash'] = 'Hanya administrator yang dapat mengubah pengaturan API Key.';
        $_SESSION['flash_type'] = 'error';
    } else {
        $apiKeyInput = trim($_POST['api_key'] ?? '');
        $saved = $googleBooksService->saveApiKey($apiKeyInput);

        if ($saved) {
            $_SESSION['flash'] = $apiKeyInput !== ''
                ? 'API Key Google Books berhasil disimpan dan diaktifkan.'
                : 'API Key Google Books dinonaktifkan. Mode kurasi aktif.';
            $_SESSION['flash_type'] = 'success';
        } else {
            $_SESSION['flash'] = 'Gagal menyimpan API Key. Periksa izin penulisan folder config.';
            $_SESSION['flash_type'] = 'error';
        }
    }

    $returnUrl = 'discover.php';
    if (!empty($_POST['return_query'])) {
        $returnUrl .= '?' . $_POST['return_query'];
    }
    header('Location: ' . $returnUrl);
    exit;
}

// 2. PROSES IMPOR BUKU DARI GOOGLE BOOKS KE LOKAL (books.json)
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['action'] ?? '') === 'import_google_book') {

    if (!isset($_SESSION['user'])) {
        $_SESSION['flash'] = 'Silakan login terlebih dahulu untuk mengimpor buku ke katalog perpustakaan.';
        $_SESSION['flash_type'] = 'warning';
        header('Location: login.php?redirect=' . urlencode('discover.php'));
        exit;
    }

    $volumeId = trim($_POST['volume_id'] ?? '');

    if ($volumeId === '') {
        $_SESSION['flash'] = 'ID buku Google Books tidak valid.';
        $_SESSION['flash_type'] = 'error';
    } else {
        // Ambil data buku lengkap dari Google Books
        $volumeData = $googleBooksService->getVolume($volumeId);

        // Jika getVolume mengembalikan null (misal rate limit 429 atau offline),
        // gunakan rekonstruksi metadata dari payload form yang sudah tampil di layar
        if ($volumeData === null && !empty($_POST['book_title'])) {
            $volumeData = [
                'id'            => $volumeId,
                'googleBooksId' => $volumeId,
                'title'         => trim($_POST['book_title'] ?? ''),
                'subtitle'      => trim($_POST['book_subtitle'] ?? ''),
                'author'        => trim($_POST['book_author'] ?? 'Penulis Tidak Diketahui'),
                'publisher'     => trim($_POST['book_publisher'] ?? 'Penerbit'),
                'publishedYear' => trim($_POST['book_year'] ?? ''),
                'isbn'          => trim($_POST['book_isbn'] ?? ''),
                'category'      => trim($_POST['book_category'] ?? 'Umum'),
                'pageCount'     => (int) ($_POST['book_pages'] ?? 0),
                'rating'        => !empty($_POST['book_rating']) ? (float) $_POST['book_rating'] : null,
                'coverImage'    => trim($_POST['book_cover'] ?? ''),
                'description'   => trim($_POST['book_description'] ?? ''),
                'previewLink'   => trim($_POST['book_preview'] ?? ''),
                'infoLink'      => trim($_POST['book_info'] ?? ''),
            ];
        }

        if ($volumeData === null) {
            $_SESSION['flash'] = 'Gagal mengambil informasi buku dari Google Books API.';
            $_SESSION['flash_type'] = 'error';
        } else {
            // Impor ke storage lokal books.json melalui BookService
            $importResult = $bookService->importFromGoogleBooks($volumeData);

            if ($importResult['success']) {
                $_SESSION['flash'] = $importResult['message'];
                $_SESSION['flash_type'] = 'success';
            } else {
                $_SESSION['flash'] = $importResult['message'];
                $_SESSION['flash_type'] = !empty($importResult['duplicate']) ? 'warning' : 'error';
            }
        }
    }

    // Redirect kembali ke halaman pencarian dengan query yang sama
    $returnUrl = 'discover.php';
    if (!empty($_POST['return_query'])) {
        $returnUrl .= '?' . $_POST['return_query'];
    }
    header('Location: ' . $returnUrl);
    exit;
}

// PARAMETER PENCARIAN & FILTER
$q          = trim($_GET['q'] ?? '');
$searchType = trim($_GET['type'] ?? 'all');
$subject    = trim($_GET['subject'] ?? '');
$orderBy    = trim($_GET['order'] ?? 'relevance');
$page       = max(1, (int) ($_GET['page'] ?? 1));

// Jika ada subject yang dipilih dari filter chip, prioritaskan subject
$activeQuery = $q;
if ($subject !== '' && $subject !== 'all') {
    $activeQuery = $q !== '' ? $q . ' subject:' . $subject : 'subject:' . $subject;
} elseif ($activeQuery === '') {
    $activeQuery = 'technology';
}

// Eksekusi pencarian ke Google Books Service
$searchResult = $googleBooksService->search($activeQuery, [
    'type'       => $searchType,
    'page'       => $page,
    'maxResults' => 20,
    'orderBy'    => $orderBy
]);

$books = $searchResult['items'] ?? [];
$totalItems = $searchResult['totalItems'] ?? 0;
$totalPages = $searchResult['totalPages'] ?? 1;
$apiWarning = $searchResult['warning'] ?? null;
$apiError   = $searchResult['error'] ?? null;
$apiSource  = $searchResult['source'] ?? 'api';
$hasApiKey  = $googleBooksService->hasApiKey();

// Daftar kategori terkurasi untuk filter chips
$featuredCategories = [
    'all'         => 'Semua Kategori',
    'Technology'  => 'Technology',
    'Programming' => 'Programming',
    'Science'     => 'Science',
    'Design'      => 'Design & UX',
    'Fiction'     => 'Fiction',
    'Business'    => 'Business',
    'Psychology'  => 'Psychology',
    'Education'   => 'Education',
];

// Mengambil pesan flash notifikasi
$message     = $_SESSION['flash'] ?? '';
$messageType = $_SESSION['flash_type'] ?? '';
unset($_SESSION['flash'], $_SESSION['flash_type']);

// Simpan parameter query URL saat ini untuk pagination & return redirect
$currentQueryArgs = $_GET;
unset($currentQueryArgs['page']);
$baseQueryString = http_build_query($currentQueryArgs);

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eksplorasi Google Books — Digital Library</title>
    <meta name="description" content="Pencarian dan eksplorasi e-book global terhubung langsung dengan Google Books API v1.">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .api-settings-panel {
            display: none;
            background: var(--bg-surface);
            border: 1px solid var(--border);
            border-radius: var(--r-lg);
            padding: var(--s-5);
            margin-bottom: var(--s-6);
            box-shadow: var(--shadow-sm);
        }
        .api-settings-panel.open {
            display: block;
        }
    </style>
</head>

<body class="page-wrapper">

    <?php require_once __DIR__ . '/templates/navbar.php'; ?>

    <main id="mainContent" class="container" style="padding: var(--s-10) 0 var(--s-16);">

        <!-- HEADER HALAMAN -->
        <div class="page-title-bar">
            <div>
                <div class="section-eyebrow">Google Books Discovery</div>
                <h1 class="serif">Eksplorasi E-Book Global</h1>
                <p>Cari jutaan publikasi dari Google Books API v1 dan impor langsung ke katalog perpustakaan lokal Anda.</p>
            </div>
            <div style="display: flex; gap: var(--s-3); align-items: center; flex-wrap: wrap;">
                <button type="button" class="btn btn-secondary btn-sm" id="btnToggleApiSettings" aria-expanded="false" aria-controls="apiSettingsPanel">
                    ⚙ Pengaturan API Key
                </button>
                <a href="index.php#katalog" class="btn btn-secondary btn-sm">
                    &larr; Kembali ke Katalog Lokal
                </a>
            </div>
        </div>

        <!-- NOTIFIKASI FLASH -->
        <?php if ($message !== ''): ?>
            <div class="alert alert-<?= htmlspecialchars($messageType) ?>" data-auto-dismiss role="status">
                <span class="alert-icon">
                    <?= $messageType === 'success' ? '✓' : ($messageType === 'warning' ? '⚠' : '✕') ?>
                </span>
                <span><?= htmlspecialchars($message) ?></span>
            </div>
        <?php endif; ?>

        <!-- PANEL PENGATURAN API KEY (COLLAPSIBLE) -->
        <div class="api-settings-panel" id="apiSettingsPanel" role="region" aria-label="Pengaturan Google Books API">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--s-3);">
                <div style="font-weight: 700; font-size: var(--text-base); color: var(--text-1);">
                    Konfigurasi Google Books API v1
                </div>
                <span class="badge <?= $hasApiKey ? 'badge-available' : 'badge-primary' ?>">
                    <?= $hasApiKey ? '● API Key Pribadi Aktif' : '○ Mode Publik & Kurasi' ?>
                </span>
            </div>
            <p style="font-size: var(--text-sm); color: var(--text-2); margin-bottom: var(--s-4); line-height: 1.5;">
                Secara default, Google Books API membatasi kuota panggilan publik anonim (HTTP 429). Jika Anda memiliki <strong>Google Cloud API Key</strong>, masukkan di bawah ini untuk kuota panggilan tanpa batas (1.000 request/hari gratis). Kunci disimpan secara lokal dan aman di <code>config/google-books.local.php</code>.
            </p>

            <?php if ($isAdmin): ?>
                <form method="POST" action="discover.php" style="display: flex; gap: var(--s-3); flex-wrap: wrap; align-items: flex-end;">
                    <input type="hidden" name="action" value="save_api_key">
                    <input type="hidden" name="return_query" value="<?= htmlspecialchars($_SERVER['QUERY_STRING'] ?? '') ?>">

                    <div style="flex: 1; min-width: 280px;">
                        <label for="apiKeyInput" class="form-label" style="margin-bottom: var(--s-1);">API Key Google Books</label>
                        <input
                            type="text"
                            name="api_key"
                            id="apiKeyInput"
                            class="form-input"
                            placeholder="Contoh: AIzaSyD..."
                            value="<?= htmlspecialchars(getenv('GOOGLE_BOOKS_API_KEY') ?: '') ?>"
                            style="margin-bottom: 0;"
                        >
                    </div>

                    <button type="submit" class="btn btn-primary btn-sm" style="height: 44px;">
                        Simpan API Key
                    </button>
                    <?php if ($hasApiKey): ?>
                        <button type="submit" name="api_key" value="" class="btn btn-secondary btn-sm" style="height: 44px;" title="Hapus API key lokal">
                            Reset ke Default
                        </button>
                    <?php endif; ?>
                </form>
            <?php else: ?>
                <div style="font-size: var(--text-xs); color: var(--text-3); font-style: italic;">
                    * Masuk sebagai Administrator jika ingin memperbarui API Key sistem.
                </div>
            <?php endif; ?>
        </div>

        <!-- PERINGATAN API / FALLBACK -->
        <?php if ($apiWarning): ?>
            <div class="alert alert-warning" role="alert" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: var(--s-3);">
                <div style="display: flex; gap: var(--s-2); align-items: center;">
                    <span class="alert-icon">ℹ</span>
                    <span><?= htmlspecialchars($apiWarning) ?></span>
                </div>
                <button type="button" class="btn btn-secondary btn-sm" id="btnQuickOpenApiSettings" style="font-size: 11px; padding: 4px 10px;">
                    Konfigurasi API Key &rarr;
                </button>
            </div>
        <?php endif; ?>

        <?php if (!empty($apiError) && empty($books)): ?>
            <div class="alert alert-error" role="alert">
                <span class="alert-icon">✕</span>
                <span><?= htmlspecialchars($apiError) ?></span>
            </div>
        <?php endif; ?>

        <!-- FORM PENCARIAN & FILTER -->
        <section class="discovery-filter-box" aria-label="Panel Pencarian Google Books">
            <form method="GET" action="discover.php">
                <div class="discovery-search-grid">
                    <div>
                        <label for="searchKeywords" class="sr-only">Kata Kunci</label>
                        <input
                            type="text"
                            name="q"
                            id="searchKeywords"
                            class="search-input"
                            style="margin-bottom: 0; padding-left: var(--s-4);"
                            placeholder="Cari judul buku, penulis, topik, atau kata kunci..."
                            value="<?= htmlspecialchars($q) ?>"
                        >
                    </div>

                    <div>
                        <label for="searchType" class="sr-only">Tipe Pencarian</label>
                        <select name="type" id="searchType" class="form-input" style="height: 50px;">
                            <option value="all" <?= $searchType === 'all' ? 'selected' : '' ?>>Semua Bidang</option>
                            <option value="title" <?= $searchType === 'title' ? 'selected' : '' ?>>Judul (intitle)</option>
                            <option value="author" <?= $searchType === 'author' ? 'selected' : '' ?>>Penulis (inauthor)</option>
                            <option value="isbn" <?= $searchType === 'isbn' ? 'selected' : '' ?>>ISBN (isbn)</option>
                            <option value="subject" <?= $searchType === 'subject' ? 'selected' : '' ?>>Subjek (subject)</option>
                        </select>
                    </div>

                    <div>
                        <label for="searchOrder" class="sr-only">Urutan</label>
                        <select name="order" id="searchOrder" class="form-input" style="height: 50px;">
                            <option value="relevance" <?= $orderBy === 'relevance' ? 'selected' : '' ?>>Relevansi</option>
                            <option value="newest" <?= $orderBy === 'newest' ? 'selected' : '' ?>>Terbaru</option>
                        </select>
                    </div>

                    <div>
                        <button type="submit" class="btn btn-primary" style="height: 50px; padding: 0 var(--s-6);">
                            Cari Buku
                        </button>
                    </div>
                </div>

                <!-- Hidden subject preserve -->
                <?php if ($subject !== '' && $subject !== 'all'): ?>
                    <input type="hidden" name="subject" value="<?= htmlspecialchars($subject) ?>">
                <?php endif; ?>
            </form>

            <!-- Filter Chips Kategori -->
            <div style="margin-top: var(--s-4); display: flex; align-items: center; gap: var(--s-2); flex-wrap: wrap;">
                <span style="font-size: var(--text-xs); font-weight: 600; color: var(--text-3); text-transform: uppercase; letter-spacing: .5px; margin-right: 4px;">
                    Filter Topik:
                </span>
                <?php foreach ($featuredCategories as $catKey => $catLabel): ?>
                    <?php
                    $isCatActive = ($subject === '' && $catKey === 'all') || ($subject === $catKey);
                    $chipParams = $_GET;
                    if ($catKey === 'all') {
                        unset($chipParams['subject']);
                    } else {
                        $chipParams['subject'] = $catKey;
                    }
                    $chipParams['page'] = 1;
                    $chipUrl = 'discover.php?' . http_build_query($chipParams);
                    ?>
                    <a
                        href="<?= htmlspecialchars($chipUrl) ?>"
                        class="filter-chip <?= $isCatActive ? 'active' : '' ?>"
                        style="text-decoration: none;"
                    >
                        <?= htmlspecialchars($catLabel) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- INFORMASI HASIL & STATUS API -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--s-5); flex-wrap: wrap; gap: var(--s-2);">
            <div style="font-size: var(--text-sm); color: var(--text-2);">
                Menampilkan <strong><?= count($books) ?></strong> buku
                <?php if ($totalItems > 0): ?>
                    dari estimasi <strong><?= number_format($totalItems) ?></strong> koleksi
                <?php endif; ?>
                (Halaman <?= $page ?>)
            </div>
            <div>
                <?php if ($apiSource === 'api'): ?>
                    <span class="badge-source badge-source-google" title="Terhubung langsung dengan server Google Books API v1">
                        ● Google Books API Live
                    </span>
                <?php elseif ($apiSource === 'cache'): ?>
                    <span class="badge-source badge-source-google" title="Dimuat dari cache lokal berkecepatan tinggi">
                        ⚡ Google Books Cache
                    </span>
                <?php else: ?>
                    <span class="badge-source" style="background: rgba(14, 165, 233, 0.1); color: #0284c7; border: 1px solid rgba(14, 165, 233, 0.25);" title="Koleksi terkurasi otomatis saat API publik dibatasi">
                        ★ Koleksi Pilihan (Fallback Mode)
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <!-- GRID HASIL PENCARIAN GOOGLE BOOKS -->
        <?php if (empty($books)): ?>
            <div class="empty-state">
                <span class="empty-state-icon" aria-hidden="true">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        <line x1="8" y1="11" x2="14" y2="11"></line>
                    </svg>
                </span>
                <h3>Buku Tidak Ditemukan</h3>
                <p>Coba gunakan kata kunci yang lebih umum, periksa ejaan penulis, atau pilih kategori topik di atas.</p>
                <a href="discover.php?subject=Technology" class="btn btn-primary btn-sm">
                    Lihat Rekomendasi Teknologi
                </a>
            </div>
        <?php else: ?>
            <div class="book-grid">
                <?php foreach ($books as $index => $book): ?>
                    <?php
                    $isAlreadyInLocal = $bookService->isDuplicate(
                        $book['title'],
                        $book['author'],
                        $book['isbn'] ?? '',
                        $book['googleBooksId'] ?? ($book['id'] ?? '')
                    );
                    $hasCover = !empty($book['coverImage']);
                    $paletteClass = 'palette-' . (($index % 5) + 1);
                    ?>
                    <article class="book-card" data-title="<?= htmlspecialchars(strtolower($book['title'])) ?>" data-author="<?= htmlspecialchars(strtolower($book['author'])) ?>">
                        <div class="book-cover-wrap">
                            <?php if ($hasCover): ?>
                                <img
                                    src="<?= htmlspecialchars($book['coverImage']) ?>"
                                    alt="Cover buku <?= htmlspecialchars($book['title']) ?>"
                                    class="book-cover-img"
                                    loading="lazy"
                                >
                            <?php else: ?>
                                <div class="book-cover-art <?= $paletteClass ?>">
                                    <span class="cover-eyebrow"><?= htmlspecialchars($book['category']) ?></span>
                                    <div class="cover-title-art"><?= htmlspecialchars($book['title']) ?></div>
                                    <div class="cover-author-art"><?= htmlspecialchars($book['author']) ?></div>
                                </div>
                            <?php endif; ?>

                            <!-- Overlay Quick Action -->
                            <div class="book-overlay">
                                <button
                                    type="button"
                                    class="btn-quick-view"
                                    data-volume-id="<?= htmlspecialchars($book['id']) ?>"
                                    data-title="<?= htmlspecialchars($book['title']) ?>"
                                    data-subtitle="<?= htmlspecialchars($book['subtitle']) ?>"
                                    data-author="<?= htmlspecialchars($book['author']) ?>"
                                    data-publisher="<?= htmlspecialchars($book['publisher']) ?>"
                                    data-published-year="<?= htmlspecialchars($book['publishedYear']) ?>"
                                    data-isbn="<?= htmlspecialchars($book['isbn']) ?>"
                                    data-category="<?= htmlspecialchars($book['category']) ?>"
                                    data-pages="<?= htmlspecialchars((string) $book['pageCount']) ?>"
                                    data-rating="<?= htmlspecialchars((string) ($book['rating'] ?? '')) ?>"
                                    data-cover="<?= htmlspecialchars($book['coverImage']) ?>"
                                    data-description="<?= htmlspecialchars($book['description']) ?>"
                                    data-preview="<?= htmlspecialchars($book['previewLink']) ?>"
                                    data-info="<?= htmlspecialchars($book['infoLink']) ?>"
                                    data-is-local="<?= $isAlreadyInLocal ? '1' : '0' ?>"
                                    aria-label="Buka detail e-book <?= htmlspecialchars($book['title']) ?>"
                                >
                                    Detail & Impor &rarr;
                                </button>
                            </div>
                        </div>

                        <div class="book-info">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--s-2);">
                                <span class="book-cat-pill" style="margin-bottom: 0;">
                                    <?= htmlspecialchars($book['category']) ?>
                                </span>
                                <?php if (!empty($book['publishedYear'])): ?>
                                    <span style="font-size: var(--text-xs); color: var(--text-3); font-weight: 500;">
                                        <?= htmlspecialchars($book['publishedYear']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <h3 class="book-title-text" title="<?= htmlspecialchars($book['title']) ?>">
                                <?= htmlspecialchars($book['title']) ?>
                            </h3>

                            <p class="book-author-text">
                                <?= htmlspecialchars($book['author']) ?>
                            </p>

                            <!-- Rating jika tersedia -->
                            <?php if ($book['rating'] !== null): ?>
                                <div class="rating-stars" style="margin-bottom: var(--s-3);">
                                    <span>★</span>
                                    <span class="rating-num">
                                        <?= number_format($book['rating'], 1) ?>
                                        <?php if ($book['ratingsCount'] > 0): ?>
                                            (<?= $book['ratingsCount'] ?>)
                                        <?php endif; ?>
                                    </span>
                                </div>
                            <?php endif; ?>

                            <div class="book-card-footer">
                                <?php if ($isAlreadyInLocal): ?>
                                    <span class="badge badge-available">
                                        Sudah di Katalog
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-primary">
                                        Google Books
                                    </span>
                                <?php endif; ?>

                                <!-- Tombol Impor Cepat dengan Fail-Safe Metadata -->
                                <form method="POST" action="discover.php">
                                    <input type="hidden" name="action" value="import_google_book">
                                    <input type="hidden" name="volume_id" value="<?= htmlspecialchars($book['id']) ?>">
                                    <input type="hidden" name="book_title" value="<?= htmlspecialchars($book['title']) ?>">
                                    <input type="hidden" name="book_subtitle" value="<?= htmlspecialchars($book['subtitle']) ?>">
                                    <input type="hidden" name="book_author" value="<?= htmlspecialchars($book['author']) ?>">
                                    <input type="hidden" name="book_publisher" value="<?= htmlspecialchars($book['publisher']) ?>">
                                    <input type="hidden" name="book_year" value="<?= htmlspecialchars($book['publishedYear']) ?>">
                                    <input type="hidden" name="book_isbn" value="<?= htmlspecialchars($book['isbn']) ?>">
                                    <input type="hidden" name="book_category" value="<?= htmlspecialchars($book['category']) ?>">
                                    <input type="hidden" name="book_pages" value="<?= htmlspecialchars((string) $book['pageCount']) ?>">
                                    <input type="hidden" name="book_rating" value="<?= htmlspecialchars((string) ($book['rating'] ?? '')) ?>">
                                    <input type="hidden" name="book_cover" value="<?= htmlspecialchars($book['coverImage']) ?>">
                                    <input type="hidden" name="book_description" value="<?= htmlspecialchars($book['description']) ?>">
                                    <input type="hidden" name="book_preview" value="<?= htmlspecialchars($book['previewLink']) ?>">
                                    <input type="hidden" name="book_info" value="<?= htmlspecialchars($book['infoLink']) ?>">
                                    <input type="hidden" name="return_query" value="<?= htmlspecialchars($_SERVER['QUERY_STRING'] ?? '') ?>">

                                    <button
                                        type="submit"
                                        class="btn btn-sm <?= $isAlreadyInLocal ? 'btn-secondary' : 'btn-primary' ?>"
                                        <?= $isAlreadyInLocal ? 'disabled style="opacity: 0.6; cursor: default;"' : '' ?>
                                        title="<?= $isAlreadyInLocal ? 'Buku sudah ada di katalog lokal' : 'Impor ke katalog perpustakaan' ?>"
                                    >
                                        <?= $isAlreadyInLocal ? '✓ Tersimpan' : '+ Impor Buku' ?>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <!-- PAGINATION -->
            <div style="display: flex; justify-content: center; align-items: center; gap: var(--s-4); margin-top: var(--s-10);">
                <?php if ($page > 1): ?>
                    <?php
                    $prevArgs = $_GET;
                    $prevArgs['page'] = $page - 1;
                    $prevUrl = 'discover.php?' . http_build_query($prevArgs);
                    ?>
                    <a href="<?= htmlspecialchars($prevUrl) ?>" class="btn btn-secondary btn-sm">
                        &larr; Halaman Sebelumnya
                    </a>
                <?php endif; ?>

                <span style="font-size: var(--text-sm); font-weight: 600; color: var(--text-2);">
                    Halaman <?= $page ?>
                </span>

                <?php if (!empty($books) && count($books) >= 20): ?>
                    <?php
                    $nextArgs = $_GET;
                    $nextArgs['page'] = $page + 1;
                    $nextUrl = 'discover.php?' . http_build_query($nextArgs);
                    ?>
                    <a href="<?= htmlspecialchars($nextUrl) ?>" class="btn btn-secondary btn-sm">
                        Halaman Berikutnya &rarr;
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </main>

    <!-- SLIDE-OVER DETAIL PREVIEW & IMPORT DRAWER -->
    <div class="drawer-backdrop" id="discoverBackdrop" aria-hidden="true"></div>
    <aside class="drawer" id="discoverDrawer" aria-labelledby="discoverDrawerTitle" aria-hidden="true" role="dialog" aria-modal="true">
        <div class="drawer-header">
            <span class="drawer-title" id="discoverDrawerTitle">Detail Buku Google Books</span>
            <button type="button" class="drawer-close" id="discoverDrawerClose" aria-label="Tutup panel preview">✕</button>
        </div>
        <div class="drawer-body">
            <img src="" alt="" id="drawerImgCover" class="drawer-cover-img" style="display: none;">

            <div class="drawer-meta">
                <div class="drawer-meta-row">
                    <span class="drawer-meta-label">Judul</span>
                    <span class="drawer-meta-value" id="drawerBookTitle">-</span>
                </div>
                <div class="drawer-meta-row" id="drawerSubtitleRow" style="display: none;">
                    <span class="drawer-meta-label">Subjudul</span>
                    <span class="drawer-meta-value" id="drawerBookSubtitle">-</span>
                </div>
                <div class="drawer-meta-row">
                    <span class="drawer-meta-label">Penulis</span>
                    <span class="drawer-meta-value" id="drawerBookAuthor">-</span>
                </div>
                <div class="drawer-meta-row">
                    <span class="drawer-meta-label">Penerbit</span>
                    <span class="drawer-meta-value" id="drawerBookPublisher">-</span>
                </div>
                <div class="drawer-meta-row">
                    <span class="drawer-meta-label">Tahun</span>
                    <span class="drawer-meta-value" id="drawerBookYear">-</span>
                </div>
                <div class="drawer-meta-row">
                    <span class="drawer-meta-label">ISBN</span>
                    <span class="drawer-meta-value" id="drawerBookIsbn">-</span>
                </div>
                <div class="drawer-meta-row">
                    <span class="drawer-meta-label">Kategori</span>
                    <span class="drawer-meta-value" id="drawerBookCategory">-</span>
                </div>
                <div class="drawer-meta-row" id="drawerPagesRow">
                    <span class="drawer-meta-label">Halaman</span>
                    <span class="drawer-meta-value" id="drawerBookPages">-</span>
                </div>
                <div class="drawer-meta-row" id="drawerRatingRow" style="display: none;">
                    <span class="drawer-meta-label">Rating</span>
                    <span class="drawer-meta-value" id="drawerBookRating">-</span>
                </div>
            </div>

            <div style="margin-bottom: var(--s-6);">
                <div style="font-size: var(--text-xs); font-weight: 700; text-transform: uppercase; letter-spacing: .7px; color: var(--text-3); margin-bottom: var(--s-2);">
                    Sinopsis & Deskripsi
                </div>
                <div id="drawerBookDesc" style="font-size: var(--text-sm); color: var(--text-2); line-height: 1.6; max-height: 180px; overflow-y: auto; padding-right: 6px;">
                    -
                </div>
            </div>

            <!-- ACTION BUTTONS -->
            <div class="drawer-actions">
                <form method="POST" action="discover.php" id="drawerImportForm">
                    <input type="hidden" name="action" value="import_google_book">
                    <input type="hidden" name="volume_id" id="drawerInputVolumeId" value="">
                    <input type="hidden" name="book_title" id="drawerInputTitle" value="">
                    <input type="hidden" name="book_subtitle" id="drawerInputSubtitle" value="">
                    <input type="hidden" name="book_author" id="drawerInputAuthor" value="">
                    <input type="hidden" name="book_publisher" id="drawerInputPublisher" value="">
                    <input type="hidden" name="book_year" id="drawerInputYear" value="">
                    <input type="hidden" name="book_isbn" id="drawerInputIsbn" value="">
                    <input type="hidden" name="book_category" id="drawerInputCategory" value="">
                    <input type="hidden" name="book_pages" id="drawerInputPages" value="">
                    <input type="hidden" name="book_rating" id="drawerInputRating" value="">
                    <input type="hidden" name="book_cover" id="drawerInputCover" value="">
                    <input type="hidden" name="book_description" id="drawerInputDesc" value="">
                    <input type="hidden" name="book_preview" id="drawerInputPreview" value="">
                    <input type="hidden" name="book_info" id="drawerInputInfo" value="">
                    <input type="hidden" name="return_query" value="<?= htmlspecialchars($_SERVER['QUERY_STRING'] ?? '') ?>">

                    <button type="submit" class="btn btn-primary btn-lg btn-full" id="drawerBtnImport">
                        + Tambahkan ke Katalog Lokal
                    </button>
                </form>

                <div style="display: flex; gap: var(--s-2); margin-top: var(--s-2);">
                    <a href="#" target="_blank" rel="noopener noreferrer" class="btn btn-secondary btn-sm" id="drawerLinkPreview" style="flex: 1; text-align: center;">
                        Preview Buku &nearr;
                    </a>
                    <a href="#" target="_blank" rel="noopener noreferrer" class="btn btn-secondary btn-sm" id="drawerLinkInfo" style="flex: 1; text-align: center;">
                        Google Books &nearr;
                    </a>
                </div>
            </div>
        </div>
    </aside>

    <?php require_once __DIR__ . '/templates/footer.php'; ?>

    <!-- Client-side script khusus untuk drawer discovery & API settings -->
    <script>
    (function() {
        const backdrop = document.getElementById('discoverBackdrop');
        const drawer = document.getElementById('discoverDrawer');
        const closeBtn = document.getElementById('discoverDrawerClose');

        const imgCover = document.getElementById('drawerImgCover');
        const titleEl = document.getElementById('drawerBookTitle');
        const subRow = document.getElementById('drawerSubtitleRow');
        const subEl = document.getElementById('drawerBookSubtitle');
        const authorEl = document.getElementById('drawerBookAuthor');
        const pubEl = document.getElementById('drawerBookPublisher');
        const yearEl = document.getElementById('drawerBookYear');
        const isbnEl = document.getElementById('drawerBookIsbn');
        const catEl = document.getElementById('drawerBookCategory');
        const pagesEl = document.getElementById('drawerBookPages');
        const ratingRow = document.getElementById('drawerRatingRow');
        const ratingEl = document.getElementById('drawerBookRating');
        const descEl = document.getElementById('drawerBookDesc');

        const inputVolId = document.getElementById('drawerInputVolumeId');
        const inputTitle = document.getElementById('drawerInputTitle');
        const inputSub = document.getElementById('drawerInputSubtitle');
        const inputAuthor = document.getElementById('drawerInputAuthor');
        const inputPub = document.getElementById('drawerInputPublisher');
        const inputYear = document.getElementById('drawerInputYear');
        const inputIsbn = document.getElementById('drawerInputIsbn');
        const inputCat = document.getElementById('drawerInputCategory');
        const inputPages = document.getElementById('drawerInputPages');
        const inputRating = document.getElementById('drawerInputRating');
        const inputCover = document.getElementById('drawerInputCover');
        const inputDesc = document.getElementById('drawerInputDesc');
        const inputPreview = document.getElementById('drawerInputPreview');
        const inputInfo = document.getElementById('drawerInputInfo');

        const btnImport = document.getElementById('drawerBtnImport');
        const linkPreview = document.getElementById('drawerLinkPreview');
        const linkInfo = document.getElementById('drawerLinkInfo');

        const apiSettingsPanel = document.getElementById('apiSettingsPanel');
        const btnToggleApiSettings = document.getElementById('btnToggleApiSettings');
        const btnQuickOpenApiSettings = document.getElementById('btnQuickOpenApiSettings');

        function toggleApiSettings() {
            if (!apiSettingsPanel) return;
            const isOpen = apiSettingsPanel.classList.toggle('open');
            if (btnToggleApiSettings) {
                btnToggleApiSettings.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            }
            if (isOpen) {
                apiSettingsPanel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        }

        if (btnToggleApiSettings) {
            btnToggleApiSettings.addEventListener('click', toggleApiSettings);
        }
        if (btnQuickOpenApiSettings) {
            btnQuickOpenApiSettings.addEventListener('click', toggleApiSettings);
        }

        function openDrawer(btn) {
            const d = btn.dataset;

            if (d.cover) {
                imgCover.src = d.cover;
                imgCover.style.display = 'block';
            } else {
                imgCover.style.display = 'none';
            }

            titleEl.textContent = d.title || '-';
            if (d.subtitle) {
                subEl.textContent = d.subtitle;
                subRow.style.display = 'grid';
            } else {
                subRow.style.display = 'none';
            }

            authorEl.textContent = d.author || '-';
            pubEl.textContent = d.publisher || '-';
            yearEl.textContent = d.publishedYear || '-';
            isbnEl.textContent = d.isbn || 'Tidak tersedia';
            catEl.textContent = d.category || '-';
            pagesEl.textContent = (d.pages && d.pages !== '0') ? d.pages + ' hlm' : '-';

            if (d.rating) {
                ratingEl.textContent = d.rating + ' / 5.0 ★';
                ratingRow.style.display = 'grid';
            } else {
                ratingRow.style.display = 'none';
            }

            descEl.innerHTML = d.description || 'Tidak ada sinopsis.';

            // Set hidden inputs untuk form impor fail-safe
            inputVolId.value = d.volumeId || '';
            inputTitle.value = d.title || '';
            inputSub.value = d.subtitle || '';
            inputAuthor.value = d.author || '';
            inputPub.value = d.publisher || '';
            inputYear.value = d.publishedYear || '';
            inputIsbn.value = d.isbn || '';
            inputCat.value = d.category || '';
            inputPages.value = d.pages || '0';
            inputRating.value = d.rating || '';
            inputCover.value = d.cover || '';
            inputDesc.value = d.description || '';
            inputPreview.value = d.preview || '';
            inputInfo.value = d.info || '';

            if (d.isLocal === '1') {
                btnImport.disabled = true;
                btnImport.textContent = '✓ Buku Sudah Ada di Katalog Lokal';
                btnImport.className = 'btn btn-secondary btn-lg btn-full';
            } else {
                btnImport.disabled = false;
                btnImport.textContent = '+ Tambahkan ke Katalog Lokal';
                btnImport.className = 'btn btn-primary btn-lg btn-full';
            }

            if (d.preview) {
                linkPreview.href = d.preview;
                linkPreview.style.display = 'inline-flex';
            } else {
                linkPreview.style.display = 'none';
            }

            if (d.info) {
                linkInfo.href = d.info;
                linkInfo.style.display = 'inline-flex';
            } else {
                linkInfo.style.display = 'none';
            }

            document.body.style.overflow = 'hidden';
            backdrop.classList.add('open');
            drawer.classList.add('open');
            drawer.setAttribute('aria-hidden', 'false');
        }

        function closeDrawer() {
            document.body.style.overflow = '';
            backdrop.classList.remove('open');
            drawer.classList.remove('open');
            drawer.setAttribute('aria-hidden', 'true');
        }

        document.querySelectorAll('.btn-quick-view').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                openDrawer(btn);
            });
        });

        if (closeBtn) closeBtn.addEventListener('click', closeDrawer);
        if (backdrop) backdrop.addEventListener('click', closeDrawer);
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && drawer && drawer.classList.contains('open')) {
                closeDrawer();
            }
        });
    })();
    </script>

</body>
</html>
