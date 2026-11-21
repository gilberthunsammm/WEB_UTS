
<?php

require_once __DIR__ . '/config/bootstrap.php';

use App\Services\BookService;
use App\Services\TransactionService;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Membuat service
$bookService = new BookService();
$transactionService = new TransactionService();

// Mengambil data
$books = $bookService->getAll();
$transactions = $transactionService->getAll();

// Menghitung jumlah member
$userFile = __DIR__ . '/data/users.json';
$users = file_exists($userFile)
    ? json_decode(file_get_contents($userFile), true)
    : [];

$totalMembers = is_array($users) ? count($users) : 0;

// Mengambil notifikasi flash
$message     = $_SESSION['flash'] ?? '';
$messageType = $_SESSION['flash_type'] ?? '';
unset($_SESSION['flash'], $_SESSION['flash_type']);

// PROSES PEMINJAMAN
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['action'] ?? '') === 'borrow') {

    // Memastikan pengguna sudah login
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
        // Menjalankan proses peminjaman
        $success = $transactionService->borrowBook($memberId, $bookId);

        if ($success) {
            $_SESSION['flash']      = 'Buku berhasil dipinjam! Cek riwayat peminjaman kamu.';
            $_SESSION['flash_type'] = 'success';
        } else {
            $_SESSION['flash']      = 'Peminjaman gagal. Buku mungkin sudah dipinjam.';
            $_SESSION['flash_type'] = 'error';
        }
    }

    header('Location: index.php#katalog');
    exit;
}

// Menghitung jumlah transaksi
$totalLoans = count($transactions);

// Variasi cover buku
$coverClasses = ['cover-one', 'cover-two', 'cover-three', 'cover-four', 'cover-five'];

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Library - Perpustakaan Digital</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

    <?php require_once __DIR__ . '/templates/navbar.php'; ?>

    <main class="container">

        <!-- HERO SECTION -->
        <section class="hero">

            <div>
                <h1>Temukan Dunia Baru<br>Melalui Buku</h1>

                <p>
                    Selamat datang di Digital Library.
                    Temukan berbagai koleksi e-book menarik
                    dan nikmati pengalaman membaca secara digital.
                </p>

                <a href="#katalog" class="btn">Jelajahi Koleksi</a>
            </div>

            <div class="hero-icon" aria-hidden="true">📖</div>

        </section>


        <!-- NOTIFIKASI FLASH -->
        <?php if ($message !== ''): ?>
            <div class="alert alert-<?= htmlspecialchars($messageType) ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>


        <!-- STATISTIK -->
        <section class="stats" aria-label="Statistik Perpustakaan">

            <div class="stat-card">
                <p>Total Koleksi Buku</p>
                <h2><?= count($books) ?></h2>
            </div>

            <div class="stat-card">
                <p>Member Terdaftar</p>
                <h2><?= $totalMembers ?></h2>
            </div>

            <div class="stat-card">
                <p>Total Peminjaman</p>
                <h2><?= $totalLoans ?></h2>
            </div>

        </section>


        <!-- KATALOG BUKU -->
        <section id="katalog">

            <div class="section-title">
                <h2>Koleksi E-book</h2>
                <span style="color: var(--color-text-light); font-size: 14px;">
                    Temukan bacaan favoritmu
                </span>
            </div>

            <!-- PENCARIAN -->
            <input
                type="text"
                id="searchInput"
                class="search-box"
                placeholder="Cari judul atau penulis buku..."
                aria-label="Cari buku berdasarkan judul atau penulis"
            >

            <!-- PESAN JIKA PENCARIAN KOSONG -->
            <div class="search-empty" id="searchEmpty" aria-live="polite">
                <h3>📭 Buku Tidak Ditemukan</h3>
                <p>Tidak ada buku yang cocok dengan pencarian kamu.<br>Coba kata kunci lain.</p>
            </div>

            <!-- DAFTAR BUKU -->
            <div class="book-grid" id="bookGrid">

                <?php if (empty($books)): ?>

                    <div class="empty-state" style="grid-column: 1 / -1;">
                        <h3>📚 Belum Ada Koleksi</h3>
                        <p>Belum ada koleksi buku tersedia saat ini.</p>
                    </div>

                <?php else: ?>

                    <?php foreach ($books as $index => $book): ?>

                        <?php
                        $coverClass = $coverClasses[$index % count($coverClasses)];
                        $isAvailable = $book->getStatus() === 'Tersedia';
                        ?>

                        <article class="book-card" data-title="<?= htmlspecialchars(strtolower($book->getTitle())) ?>" data-author="<?= htmlspecialchars(strtolower($book->getAuthor())) ?>">

                            <div class="book-cover <?= $coverClass ?>" aria-hidden="true">
                                📚
                            </div>

                            <span class="category">
                                <?= htmlspecialchars($book->getCategory()) ?>
                            </span>

                            <span class="status-badge <?= $isAvailable ? 'available' : 'borrowed' ?>">
                                <?= $isAvailable ? '✓ Tersedia' : '⏳ Dipinjam' ?>
                            </span>

                            <h3><?= htmlspecialchars($book->getTitle()) ?></h3>

                            <p class="author">
                                ✍️ <?= htmlspecialchars($book->getAuthor()) ?>
                            </p>

                            <!-- FORM PEMINJAMAN -->
                            <form method="POST" action="index.php#katalog">

                                <input type="hidden" name="action" value="borrow">
                                <input type="hidden" name="book_id" value="<?= $book->getId() ?>">

                                <button
                                    type="submit"
                                    class="btn"
                                    <?= !$isAvailable ? 'disabled aria-disabled="true"' : '' ?>
                                    style="<?= !$isAvailable ? 'opacity: 0.5; cursor: not-allowed;' : '' ?>"
                                >
                                    <?= $isAvailable ? '📖 Pinjam Buku' : 'Tidak Tersedia' ?>
                                </button>

                            </form>

                        </article>

                    <?php endforeach; ?>

                <?php endif; ?>

            </div>

        </section>

    </main>

    <?php require_once __DIR__ . '/templates/footer.php'; ?>

</body>
</html>
