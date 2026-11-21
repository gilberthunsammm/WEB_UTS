
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

// Mengambil notifikasi
$message = $_SESSION['flash'] ?? '';
$messageType = $_SESSION['flash_type'] ?? '';

unset($_SESSION['flash'], $_SESSION['flash_type']);

// PROSES PEMINJAMAN
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['action'] ?? '') === 'borrow') {

    // Memastikan pengguna sudah login
    if (!isset($_SESSION['user'])) {
        header('Location: login.php');
        exit;
    }

    $memberId = (int) $_SESSION['user']['id'];
    $bookId = (int) ($_POST['book_id'] ?? 0);

    if ($bookId <= 0 || $memberId <= 0) {

        $_SESSION['flash'] = 'Data peminjaman tidak valid.';
        $_SESSION['flash_type'] = 'error';

    } else {

        // Menjalankan proses peminjaman
        $success = $transactionService->borrowBook(
            $memberId,
            $bookId
        );

        if ($success) {
            $_SESSION['flash'] = 'Buku berhasil dipinjam!';
            $_SESSION['flash_type'] = 'success';
        } else {
            $_SESSION['flash'] = 'Peminjaman gagal. Buku mungkin sudah dipinjam.';
            $_SESSION['flash_type'] = 'error';
        }
    }

    header('Location: index.php#katalog');
    exit;
}

// Menghitung jumlah transaksi
$totalLoans = count($transactions);

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Digital Library - Perpustakaan Digital</title>

    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

    <!-- NAVBAR -->
    <header class="navbar">

        <div class="container nav-content">

            <div class="logo">📚 Digital Library</div>

            <nav class="nav-links">

                <a href="index.php">Beranda</a>

                <a href="#katalog">Katalog</a>

                <?php if (isset($_SESSION['user'])): ?>

                    <a href="dashboard.php">Dashboard</a>

                <?php else: ?>

                    <a href="login.php">Login</a>

                    <a href="register.php" class="btn">
                        Daftar Member
                    </a>

                <?php endif; ?>

            </nav>

        </div>

    </header>


    <main class="container">

        <!-- HERO SECTION -->
        <section class="hero">

            <div>

                <h1>
                    Temukan Dunia Baru<br>
                    Melalui Buku
                </h1>

                <p>
                    Selamat datang di Digital Library.
                    Temukan berbagai koleksi e-book menarik
                    dan nikmati pengalaman membaca secara digital.
                </p>

                <a href="#katalog" class="btn">
                    Jelajahi Koleksi
                </a>

            </div>

            <div class="hero-icon">📖</div>

        </section>


        <!-- NOTIFIKASI -->
        <?php if ($message !== ''): ?>

            <div style="
                margin-top: 25px;
                padding: 15px;
                border-radius: 10px;
                background: <?= $messageType === 'success' ? '#dcfce7' : '#fee2e2' ?>;
                color: <?= $messageType === 'success' ? '#166534' : '#991b1b' ?>;
            ">
                <?= htmlspecialchars($message) ?>
            </div>

        <?php endif; ?>


        <!-- STATISTIK -->
        <section class="stats">

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

                <span>Temukan bacaan favoritmu</span>

            </div>


            <!-- PENCARIAN -->
            <input
                type="text"
                id="searchInput"
                class="search-box"
                placeholder="Cari judul atau penulis buku..."
                aria-label="Cari buku"
            >


            <!-- DAFTAR BUKU -->
            <div class="book-grid">

                <?php if (empty($books)): ?>

                    <p>Belum ada koleksi buku tersedia.</p>

                <?php else: ?>

                    <?php foreach ($books as $book): ?>

                        <article class="book-card">

                            <div class="book-cover cover-one">
                                📚
                            </div>

                            <span class="category">
                                <?= htmlspecialchars($book->getCategory()) ?>
                            </span>

                            <h3>
                                <?= htmlspecialchars($book->getTitle()) ?>
                            </h3>

                            <p class="author">
                                Penulis:
                                <?= htmlspecialchars($book->getAuthor()) ?>
                            </p>

                            <p>
                                Status:
                                <?= htmlspecialchars($book->getStatus()) ?>
                            </p>


                            <!-- FORM PEMINJAMAN -->
                            <form method="POST" action="index.php#katalog">

                                <input
                                    type="hidden"
                                    name="action"
                                    value="borrow"
                                >

                                <input
                                    type="hidden"
                                    name="book_id"
                                    value="<?= $book->getId() ?>"
                                >

                                <button
                                    type="submit"
                                    class="btn"
                                    style="width: 100%;"
                                    <?= $book->getStatus() !== 'Tersedia' ? 'disabled' : '' ?>
                                >

                                    <?= $book->getStatus() === 'Tersedia'
                                        ? 'Pinjam Buku'
                                        : 'Tidak Tersedia'
                                    ?>

                                </button>

                            </form>

                        </article>

                    <?php endforeach; ?>

                <?php endif; ?>

            </div>

        </section>

    </main>


    <!-- FOOTER -->
    <footer>

        <p>
            &copy; 2026 Digital Library.
            All Rights Reserved.
        </p>

    </footer>


    <!-- JAVASCRIPT -->
    <script src="assets/js/script.js"></script>

</body>

</html>
