<?php

require_once __DIR__ . '/config/bootstrap.php';

use App\Services\TransactionService;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'];
$transactionService = new TransactionService();

$message     = '';
$messageType = '';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// PROSES PENGEMBALIAN BUKU
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $transactionId = (int) ($_POST['transaction_id'] ?? 0);
    $csrfToken     = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $csrfToken)) {
        $message     = 'Token keamanan tidak valid. Silakan coba kembali.';
        $messageType = 'error';
    } else {
        $success = $transactionService->returnBook($transactionId, (int) $user['id']);

        if ($success) {
            header('Location: my-loans.php?status=returned');
            exit;
        } else {
            $message     = 'Proses pengembalian e-book tidak berhasil. Pastikan status masih dalam peminjaman aktif.';
            $messageType = 'error';
        }
    }
}

if (isset($_GET['status']) && $_GET['status'] === 'returned') {
    $message     = 'E-Book berhasil dikembalikan! Terima kasih telah mengembalikan tepat waktu.';
    $messageType = 'success';
}

$transactions  = $transactionService->getByMemberId((int) $user['id']);
$activeLoans   = 0;
$returnedLoans = 0;

foreach ($transactions as $transaction) {
    if ($transaction->getStatus() === 'Dipinjam') {
        $activeLoans++;
    }
    if ($transaction->getStatus() === 'Dikembalikan') {
        $returnedLoans++;
    }
}

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Peminjaman — Digital Library</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body class="page-wrapper">

    <?php require_once __DIR__ . '/templates/navbar.php'; ?>

    <main class="container loans-layout">

        <div class="page-title-bar">
            <div>
                <div class="section-eyebrow">Aktivitas Saya</div>
                <h1 class="serif">Riwayat Peminjaman E-Book</h1>
                <p>Pantau status pinjaman buku Anda dan lakukan pengembalian secara mandiri.</p>
            </div>
            <div>
                <a href="index.php#katalog" class="btn btn-primary btn-sm">
                    + Pinjam E-Book Lain
                </a>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?= htmlspecialchars($messageType) ?>" data-auto-dismiss role="status">
                <span class="alert-icon">
                    <?= $messageType === 'success' ? '✓' : '✕' ?>
                </span>
                <span><?= htmlspecialchars($message) ?></span>
            </div>
        <?php endif; ?>

        <!-- SUMMARY STATS -->
        <section class="loans-summary" aria-label="Statistik Pinjaman Anggota">
            <div class="stat-card">
                <div class="stat-icon-wrap" aria-hidden="true">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                </div>
                <div>
                    <div class="stat-num" data-count="<?= $activeLoans ?>"><?= $activeLoans ?></div>
                    <div class="stat-label">Buku Sedang Dipinjam</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon-wrap" aria-hidden="true">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                    </svg>
                </div>
                <div>
                    <div class="stat-num" data-count="<?= count($transactions) ?>"><?= count($transactions) ?></div>
                    <div class="stat-label">Total Transaksi Selesai</div>
                </div>
            </div>
        </section>

        <!-- LOANS TABLE -->
        <section class="dash-card" aria-labelledby="loansTableTitle">
            <div class="dash-card-header">
                <div>
                    <h2 class="dash-card-title" id="loansTableTitle">Daftar Transaksi Peminjaman</h2>
                    <p class="dash-card-sub">Seluruh riwayat peminjaman e-book yang tercatat atas akun Anda.</p>
                </div>
            </div>

            <?php if (empty($transactions)): ?>
                <div class="empty-state">
                    <span class="empty-state-icon" aria-hidden="true">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                        </svg>
                    </span>
                    <h3>Belum Ada Peminjaman</h3>
                    <p>Anda belum pernah melakukan transaksi peminjaman buku.</p>
                    <a href="index.php#katalog" class="btn btn-primary">
                        Mulai Eksplorasi Katalog
                    </a>
                </div>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="data-table" aria-label="Tabel riwayat peminjaman buku">
                        <thead>
                            <tr>
                                <th scope="col">No</th>
                                <th scope="col">Judul E-Book</th>
                                <th scope="col">Tanggal Pinjam</th>
                                <th scope="col">Status</th>
                                <th scope="col" style="text-align: right;">Aksi Pengembalian</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $idx => $t): ?>
                                <tr>
                                    <td><?= $idx + 1 ?></td>
                                    <td><strong><?= htmlspecialchars($t->getBookTitle()) ?></strong></td>
                                    <td><?= htmlspecialchars($t->getBorrowDate()) ?></td>
                                    <td>
                                        <?php if ($t->getStatus() === 'Dipinjam'): ?>
                                            <span class="badge badge-borrowed">Sedang Dipinjam</span>
                                        <?php else: ?>
                                            <span class="badge badge-available">Dikembalikan</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: right;">
                                        <?php if ($t->getStatus() === 'Dipinjam'): ?>
                                            <form
                                                method="POST"
                                                action="my-loans.php"
                                                style="display: inline;"
                                                data-confirm="Apakah Anda yakin ingin mengembalikan buku ini sekarang?"
                                                data-confirm-title="Pengembalian E-Book"
                                            >
                                                <input type="hidden" name="transaction_id" value="<?= $t->getId() ?>">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                                <button type="submit" class="btn btn-success btn-sm">
                                                    ↩ Kembalikan Buku
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span style="font-size: var(--text-xs); color: var(--text-3); font-weight: 500;">
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
        </section>

    </main>

    <?php require_once __DIR__ . '/templates/footer.php'; ?>

</body>
</html>
