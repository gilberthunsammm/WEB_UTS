<?php

require_once __DIR__ . '/config/bootstrap.php';

use App\Services\TransactionService;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Memastikan member sudah login
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'];

$transactionService = new TransactionService();

$message = '';
$messageType = '';

// Token keamanan form
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Memproses pengembalian buku
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $transactionId = (int) ($_POST['transaction_id'] ?? 0);
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $csrfToken)) {

        $message = 'Permintaan tidak valid.';
        $messageType = 'error';

    } else {

        $success = $transactionService->returnBook(
            $transactionId,
            (int) $user['id']
        );

        if ($success) {
            header('Location: my-loans.php?status=returned');
            exit;
        } else {
            $message = 'Pengembalian buku gagal diproses.';
            $messageType = 'error';
        }
    }
}

// Pesan setelah pengembalian
if (isset($_GET['status']) && $_GET['status'] === 'returned') {
    $message = 'Buku berhasil dikembalikan!';
    $messageType = 'success';
}

// Mengambil transaksi milik member
$transactions = $transactionService->getByMemberId(
    (int) $user['id']
);

// Menghitung buku yang masih dipinjam
$activeLoans = 0;

foreach ($transactions as $transaction) {
    if ($transaction->getStatus() === 'Dipinjam') {
        $activeLoans++;
    }
}

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Riwayat Peminjaman - Digital Library</title>

    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        .history-section {
            margin: 40px auto;
        }

        .history-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            border: 1px solid #eee;
        }

        .history-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .history-table th,
        .history-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .history-table th {
            background: #eee7ff;
            color: #7040c9;
        }

        .status-borrowed {
            color: #d97706;
            font-weight: bold;
        }

        .status-returned {
            color: #16a34a;
            font-weight: bold;
        }

        .message {
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }

        .success {
            background: #dcfce7;
            color: #166534;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
        }

        .return-btn {
            background: #16a34a;
            color: white;
            border: none;
            padding: 9px 14px;
            border-radius: 7px;
            cursor: pointer;
        }

        .return-btn:hover {
            background: #15803d;
        }

        .empty-history {
            text-align: center;
            padding: 35px;
            color: #777;
        }

        .history-summary {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 25px;
        }

        @media (max-width: 650px) {
            .history-summary {
                grid-template-columns: 1fr;
            }

            .history-table {
                font-size: 13px;
            }

            .history-table th,
            .history-table td {
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
                <a href="dashboard.php">Dashboard</a>
                <a href="my-loans.php">Riwayat Peminjaman</a>
            </nav>

        </div>
    </header>


    <main class="container history-section">

        <h1>Riwayat Peminjaman</h1>

        <p>
            Halo, <?= htmlspecialchars($user['name']) ?>!
            Berikut adalah daftar peminjaman buku kamu.
        </p>


        <!-- PESAN NOTIFIKASI -->
        <?php if ($message): ?>

            <div class="message <?= $messageType ?>">
                <?= htmlspecialchars($message) ?>
            </div>

        <?php endif; ?>


        <!-- RINGKASAN -->
        <div class="history-summary">

            <div class="stat-card">
                <p>Total Riwayat Peminjaman</p>
                <h2><?= count($transactions) ?></h2>
            </div>

            <div class="stat-card">
                <p>Buku Sedang Dipinjam</p>
                <h2><?= $activeLoans ?></h2>
            </div>

        </div>


        <!-- DAFTAR RIWAYAT -->
        <div class="history-card">

            <h2>Daftar Buku yang Dipinjam</h2>

            <?php if (empty($transactions)): ?>

                <div class="empty-history">

                    <h3>📚 Belum Ada Peminjaman</h3>

                    <p>
                        Kamu belum pernah meminjam buku.
                    </p>

                    <a href="index.php#katalog" class="btn">
                        Jelajahi Koleksi
                    </a>

                </div>

            <?php else: ?>

                <div style="overflow-x: auto;">

                    <table class="history-table">

                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Judul Buku</th>
                                <th>Tanggal Peminjaman</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php foreach ($transactions as $index => $transaction): ?>

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

                                    <td>

                                        <?php if ($transaction->getStatus() === 'Dipinjam'): ?>

                                            <form method="POST"
                                                  onsubmit="return confirm('Apakah kamu yakin ingin mengembalikan buku ini?');">

                                                <input
                                                    type="hidden"
                                                    name="transaction_id"
                                                    value="<?= $transaction->getId() ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="csrf_token"
                                                    value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    class="return-btn"
                                                >
                                                    Kembalikan
                                                </button>

                                            </form>

                                        <?php else: ?>

                                            <span style="color: #999;">
                                                Selesai
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php endif; ?>

        </div>


        <a href="index.php" class="btn">
            Kembali ke Beranda
        </a>

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
