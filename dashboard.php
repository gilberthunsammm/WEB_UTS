<?php

require_once __DIR__ . '/config/bootstrap.php';

use App\Services\BookService;
use App\Services\UserService;
use App\Services\TransactionService;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Memastikan pengguna sudah login
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'];

// Proses logout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
    $_SESSION = [];
    session_destroy();
    header('Location: login.php');
    exit;
}

// Mengambil objek Member sesungguhnya untuk memanfaatkan Polymorphism
$userService  = new UserService();
$memberObject = $userService->findByEmail($user['email']);

// Pesan sambutan dinamis dari method polimorfik getDashboardMessage()
// Method ini di-override oleh class Member (Polymorphism via Method Overriding)
$welcomeMessage = $memberObject
    ? $memberObject->getDashboardMessage()
    : 'Selamat datang di Digital Library.';

// Mengambil data buku
$bookService = new BookService();
$books       = $bookService->getAll();
$totalBooks  = count($books);

// Mengambil transaksi milik member
$transactionService = new TransactionService();
$transactions       = $transactionService->getByMemberId((int) $user['id']);

// Menghitung statistik peminjaman
$totalLoans    = count($transactions);
$activeLoans   = 0;
$returnedLoans = 0;

foreach ($transactions as $transaction) {
    if ($transaction->getStatus() === 'Dipinjam')    $activeLoans++;
    if ($transaction->getStatus() === 'Dikembalikan') $returnedLoans++;
}

// Mengambil 5 aktivitas terbaru
$recentTransactions = array_slice(array_reverse($transactions), 0, 5);

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Member - Digital Library</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

    <?php require_once __DIR__ . '/templates/navbar.php'; ?>

    <main class="container dashboard">

        <!-- WELCOME CARD — pesan dari getDashboardMessage() (Polymorphism) -->
        <section class="welcome-card">
            <h1>Halo, <?= htmlspecialchars($user['name']) ?>! 👋</h1>
            <p><?= htmlspecialchars($welcomeMessage) ?></p>
        </section>


        <!-- STATISTIK -->
        <section class="dashboard-grid" aria-label="Statistik peminjaman">

            <div class="dashboard-card">
                <p>📚 Total Koleksi Buku</p>
                <h2><?= $totalBooks ?></h2>
            </div>

            <div class="dashboard-card">
                <p>📖 Total Peminjaman</p>
                <h2><?= $totalLoans ?></h2>
            </div>

            <div class="dashboard-card">
                <p>⏳ Sedang Dipinjam</p>
                <h2><?= $activeLoans ?></h2>
            </div>

            <div class="dashboard-card">
                <p>✅ Dikembalikan</p>
                <h2><?= $returnedLoans ?></h2>
            </div>

        </section>


        <!-- AKTIVITAS TERBARU -->
        <section class="activity-card">

            <h2>Aktivitas Peminjaman Terbaru</h2>
            <p style="color: var(--color-text-light); font-size: 14px; margin-bottom: 10px;">
                5 transaksi terakhir
            </p>

            <?php if (empty($recentTransactions)): ?>

                <div class="empty-state">
                    <h3>📚 Belum Ada Aktivitas</h3>
                    <p>Kamu belum pernah melakukan peminjaman buku.</p>
                    <a href="index.php#katalog" class="btn">Jelajahi Koleksi</a>
                </div>

            <?php else: ?>

                <div class="table-wrapper">
                    <table class="data-table" aria-label="Daftar aktivitas terbaru">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Judul Buku</th>
                                <th>Tanggal Pinjam</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentTransactions as $index => $transaction): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= htmlspecialchars($transaction->getBookTitle()) ?></td>
                                    <td><?= htmlspecialchars($transaction->getBorrowDate()) ?></td>
                                    <td>
                                        <?php if ($transaction->getStatus() === 'Dipinjam'): ?>
                                            <span class="status-borrowed">Dipinjam</span>
                                        <?php else: ?>
                                            <span class="status-returned">Dikembalikan</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php endif; ?>

            <div class="action-buttons">
                <a href="my-loans.php" class="btn">Lihat Semua Riwayat</a>
                <a href="index.php#katalog" class="btn">Pinjam Buku</a>
            </div>

        </section>


        <!-- INFORMASI AKUN -->
        <section class="account-card">

            <h2>Informasi Akun Saya</h2>
            <p style="color: var(--color-text-light); font-size: 14px;">
                Berikut informasi akun yang sedang digunakan.
            </p>

            <div class="account-info">
                <strong>ID Member</strong>
                <span><?= htmlspecialchars((string) $user['id']) ?></span>

                <strong>Nama Lengkap</strong>
                <span><?= htmlspecialchars($user['name']) ?></span>

                <strong>Email</strong>
                <span><?= htmlspecialchars($user['email']) ?></span>

                <strong>Role</strong>
                <span><?= htmlspecialchars($user['role']) ?></span>

                <?php if ($memberObject): ?>
                    <strong>Tanggal Bergabung</strong>
                    <span><?= htmlspecialchars($memberObject->getRegistrationDate()) ?></span>
                <?php endif; ?>
            </div>

            <div class="action-buttons">

                <a href="index.php#katalog" class="btn">Jelajahi Koleksi Buku</a>

                <form method="POST">
                    <button
                        type="submit"
                        name="logout"
                        value="1"
                        class="btn"
                        style="background: var(--color-danger);"
                        onclick="return confirm('Apakah kamu yakin ingin logout?')"
                    >
                        Logout
                    </button>
                </form>

            </div>

        </section>

    </main>

    <?php require_once __DIR__ . '/templates/footer.php'; ?>

</body>
</html>
