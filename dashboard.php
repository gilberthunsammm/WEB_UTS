<?php

require_once __DIR__ . '/config/bootstrap.php';

use App\Services\BookService;
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
if ($_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['logout'])) {

    $_SESSION = [];

    session_destroy();

    header('Location: login.php');
    exit;
}

// Mengambil data buku
$bookService = new BookService();
$books = $bookService->getAll();

$totalBooks = count($books);

// Mengambil transaksi milik member
$transactionService = new TransactionService();

$transactions = $transactionService->getByMemberId(
    (int) $user['id']
);

// Menghitung statistik peminjaman
$totalLoans = count($transactions);
$activeLoans = 0;
$returnedLoans = 0;

foreach ($transactions as $transaction) {

    if ($transaction->getStatus() === 'Dipinjam') {
        $activeLoans++;
    }

    if ($transaction->getStatus() === 'Dikembalikan') {
        $returnedLoans++;
    }
}

// Mengambil 5 aktivitas terbaru
$recentTransactions = array_slice(
    array_reverse($transactions),
    0,
    5
);

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Dashboard Member - Digital Library</title>

    <link rel="stylesheet"
          href="assets/css/style.css">

    <style>

        .dashboard {
            padding: 40px 0;
        }

        .welcome-card {
            background: linear-gradient(120deg, #7040c8, #a477e8);
            color: white;
            padding: 40px;
            border-radius: 18px;
            margin-bottom: 30px;
        }

        .welcome-card h1 {
            margin-bottom: 12px;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .dashboard-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            border: 1px solid #eee;
        }

        .dashboard-card h2 {
            color: #7040c8;
            margin-top: 12px;
        }

        .account-card,
        .activity-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            border: 1px solid #eee;
        }

        .account-info {
            display: grid;
            grid-template-columns: 150px 1fr;
            gap: 15px;
            margin-top: 20px;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin-top: 25px;
        }

        .logout-button {
            background: #e63946;
            color: white;
            border: none;
            padding: 12px 22px;
            border-radius: 8px;
            cursor: pointer;
        }

        .activity-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .activity-table th,
        .activity-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .activity-table th {
            background: #eee7ff;
            color: #7040c8;
        }

        .status-borrowed {
            color: #d97706;
            font-weight: bold;
        }

        .status-returned {
            color: #16a34a;
            font-weight: bold;
        }

        .empty-activity {
            text-align: center;
            padding: 30px;
            color: #777;
        }

        @media (max-width: 900px) {
            .dashboard-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 600px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }

            .account-info {
                grid-template-columns: 1fr;
                gap: 5px;
            }

            .activity-table {
                font-size: 13px;
            }

            .activity-table th,
            .activity-table td {
                padding: 9px;
            }
        }

    </style>

</head>

<body>

<!-- NAVBAR -->

<header class="navbar">

    <div class="container nav-content">

        <div class="logo">
            📚 Digital Library
        </div>

        <nav class="nav-links">

            <a href="index.php">Beranda</a>

            <a href="index.php#katalog">Katalog Buku</a>

            <a href="dashboard.php">Dashboard</a>

            <a href="my-loans.php">Riwayat Peminjaman</a>

        </nav>

    </div>

</header>


<main class="container dashboard">

    <!-- WELCOME -->

    <section class="welcome-card">

        <h1>
            Halo, <?= htmlspecialchars($user['name']) ?>! 👋
        </h1>

        <p>
            Selamat datang di Dashboard Member Digital Library.
        </p>

        <p>
            Pantau aktivitas dan riwayat peminjaman buku kamu di sini.
        </p>

    </section>


    <!-- STATISTIK -->

    <section class="dashboard-grid">

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

        <?php if (empty($recentTransactions)): ?>

            <div class="empty-activity">

                <h3>📚 Belum Ada Aktivitas</h3>

                <p>
                    Kamu belum pernah melakukan peminjaman buku.
                </p>

                <a href="index.php#katalog" class="btn">
                    Jelajahi Koleksi
                </a>

            </div>

        <?php else: ?>

            <div style="overflow-x: auto;">

                <table class="activity-table">

                    <thead>

                        <tr>
                            <th>No</th>
                            <th>Judul Buku</th>
                            <th>Tanggal</th>
                            <th>Status</th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach ($recentTransactions as $index => $transaction): ?>

                            <tr>

                                <td>
                                    <?= $index + 1 ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($transaction->getBookTitle()) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($transaction->getBorrowDate()) ?>
                                </td>

                                <td>

                                    <?php if ($transaction->getStatus() === 'Dipinjam'): ?>

                                        <span class="status-borrowed">
                                            Dipinjam
                                        </span>

                                    <?php else: ?>

                                        <span class="status-returned">
                                            Dikembalikan
                                        </span>

                                    <?php endif; ?>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>

        <div class="action-buttons">

            <a href="my-loans.php" class="btn">
                Lihat Semua Riwayat
            </a>

            <a href="index.php#katalog" class="btn">
                Pinjam Buku
            </a>

        </div>

    </section>


    <!-- INFORMASI AKUN -->

    <section class="account-card">

        <h2>Informasi Akun Saya</h2>

        <p>
            Berikut informasi akun yang sedang digunakan.
        </p>

        <div class="account-info">

            <strong>ID Member</strong>

            <span>
                <?= htmlspecialchars((string) $user['id']) ?>
            </span>

            <strong>Nama Lengkap</strong>

            <span>
                <?= htmlspecialchars($user['name']) ?>
            </span>

            <strong>Email</strong>

            <span>
                <?= htmlspecialchars($user['email']) ?>
            </span>

            <strong>Role</strong>

            <span>
                <?= htmlspecialchars($user['role']) ?>
            </span>

        </div>

        <div class="action-buttons">

            <a href="index.php#katalog" class="btn">
                Jelajahi Koleksi Buku
            </a>

            <form method="POST">

                <button
                    type="submit"
                    name="logout"
                    value="1"
                    class="logout-button"
                    onclick="return confirm('Apakah kamu yakin ingin logout?')"
                >
                    Logout
                </button>

            </form>

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

</body>
</html>
