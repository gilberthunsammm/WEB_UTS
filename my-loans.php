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

$message     = '';
$messageType = '';

// Inisialisasi CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Memproses pengembalian buku
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $transactionId = (int) ($_POST['transaction_id'] ?? 0);
    $csrfToken     = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $csrfToken)) {

        $message     = 'Permintaan tidak valid. Silakan coba lagi.';
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
            $message     = 'Pengembalian buku gagal diproses. Pastikan buku masih berstatus dipinjam.';
            $messageType = 'error';
        }
    }
}

// Pesan setelah pengembalian berhasil
if (isset($_GET['status']) && $_GET['status'] === 'returned') {
    $message     = '✅ Buku berhasil dikembalikan! Status buku telah diperbarui ke Tersedia.';
    $messageType = 'success';
}

// Mengambil transaksi milik member
$transactions = $transactionService->getByMemberId((int) $user['id']);

// Menghitung statistik
$activeLoans   = 0;
$returnedLoans = 0;

foreach ($transactions as $transaction) {
    if ($transaction->getStatus() === 'Dipinjam')    $activeLoans++;
    if ($transaction->getStatus() === 'Dikembalikan') $returnedLoans++;
}

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Peminjaman - Digital Library</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

    <?php require_once __DIR__ . '/templates/navbar.php'; ?>

    <main class="container history-section">

        <h1>Riwayat Peminjaman</h1>

        <p style="color: var(--color-text-light); margin: 8px 0 20px;">
            Halo, <strong><?= htmlspecialchars($user['name']) ?></strong>!
            Berikut adalah daftar seluruh peminjaman buku kamu.
        </p>

        <!-- PESAN NOTIFIKASI -->
        <?php if ($message): ?>
            <div class="alert alert-<?= htmlspecialchars($messageType) ?>" role="<?= $messageType === 'success' ? 'status' : 'alert' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>


        <!-- RINGKASAN STATISTIK -->
        <div class="history-summary">

            <div class="stat-card">
                <p>Total Riwayat Peminjaman</p>
                <h2><?= count($transactions) ?></h2>
            </div>

            <div class="stat-card">
                <p>⏳ Sedang Dipinjam</p>
                <h2><?= $activeLoans ?></h2>
            </div>

        </div>


        <!-- DAFTAR RIWAYAT -->
        <div class="history-card">

            <h2>Daftar Buku yang Dipinjam</h2>

            <?php if (empty($transactions)): ?>

                <div class="empty-state">
                    <h3>📚 Belum Ada Peminjaman</h3>
                    <p>Kamu belum pernah meminjam buku.</p>
                    <a href="index.php#katalog" class="btn">Jelajahi Koleksi</a>
                </div>

            <?php else: ?>

                <div class="table-wrapper">

                    <table class="data-table" aria-label="Riwayat peminjaman buku">

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

                                    <td>
                                        <?php if ($transaction->getStatus() === 'Dipinjam'): ?>

                                            <form
                                                method="POST"
                                                onsubmit="return confirm('Kembalikan buku ini?\n\n<?= htmlspecialchars($transaction->getBookTitle()) ?>')"
                                            >
                                                <input type="hidden" name="transaction_id" value="<?= $transaction->getId() ?>">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                                                <button type="submit" class="return-btn" aria-label="Kembalikan buku <?= htmlspecialchars($transaction->getBookTitle()) ?>">
                                                    ↩ Kembalikan
                                                </button>
                                            </form>

                                        <?php else: ?>
                                            <span class="status-returned">✓ Selesai</span>
                                        <?php endif; ?>
                                    </td>

                                </tr>
                            <?php endforeach; ?>
                        </tbody>

                    </table>

                </div>

            <?php endif; ?>

        </div>


        <div style="margin-top: 10px;">
            <a href="index.php" class="btn">← Kembali ke Beranda</a>
        </div>

    </main>

    <?php require_once __DIR__ . '/templates/footer.php'; ?>

</body>
</html>
