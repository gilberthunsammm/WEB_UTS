<?php

require_once __DIR__ . '/config/bootstrap.php';

use App\Services\BookService;
use App\Services\UserService;
use App\Services\TransactionService;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
    $_SESSION = [];
    session_destroy();
    header('Location: login.php');
    exit;
}

$userService  = new UserService();
$memberObject = $userService->findByEmail($user['email']);

// Polymorphic message invocation
$welcomeMessage = $memberObject
    ? $memberObject->getDashboardMessage()
    : 'Selamat datang di portal Digital Library.';

$bookService = new BookService();
$books       = $bookService->getAll();
$totalBooks  = count($books);

$transactionService = new TransactionService();
$transactions       = $transactionService->getByMemberId((int) $user['id']);

$totalLoans    = count($transactions);
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

$recentTransactions = array_slice(array_reverse($transactions), 0, 5);

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Member — Digital Library</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body class="page-wrapper">

    <?php require_once __DIR__ . '/templates/navbar.php'; ?>

    <main class="container dash-layout">

        <!-- WELCOME BANNER (Polymorphic Message) -->
        <section class="welcome-banner" aria-labelledby="welcomeTitle">
            <div class="welcome-eyebrow">Portal Anggota Perpustakaan</div>
            <h1 id="welcomeTitle">Halo, <?= htmlspecialchars($user['name']) ?></h1>
            <p><?= htmlspecialchars($welcomeMessage) ?></p>
        </section>

        <!-- DASHBOARD STATS -->
        <section class="dash-stats" aria-label="Ringkasan Statistik Anggota">
            <div class="dash-stat">
                <span class="dash-stat-icon" aria-hidden="true">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                    </svg>
                </span>
                <div class="dash-stat-num" data-count="<?= $totalBooks ?>"><?= $totalBooks ?></div>
                <div class="dash-stat-label">Total Koleksi E-Book</div>
            </div>
            <div class="dash-stat">
                <span class="dash-stat-icon" aria-hidden="true">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path>
                        <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>
                    </svg>
                </span>
                <div class="dash-stat-num" data-count="<?= $totalLoans ?>"><?= $totalLoans ?></div>
                <div class="dash-stat-label">Total Dipinjam</div>
            </div>
            <div class="dash-stat">
                <span class="dash-stat-icon" aria-hidden="true">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                </span>
                <div class="dash-stat-num" data-count="<?= $activeLoans ?>"><?= $activeLoans ?></div>
                <div class="dash-stat-label">Sedang Dipinjam</div>
            </div>
            <div class="dash-stat">
                <span class="dash-stat-icon" aria-hidden="true">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                </span>
                <div class="dash-stat-num" data-count="<?= $returnedLoans ?>"><?= $returnedLoans ?></div>
                <div class="dash-stat-label">Telah Dikembalikan</div>
            </div>
        </section>

        <!-- RECENT ACTIVITY CARD -->
        <section class="dash-card" aria-labelledby="recentActivityTitle">
            <div class="dash-card-header">
                <div>
                    <h2 class="dash-card-title" id="recentActivityTitle">Aktivitas Peminjaman Terbaru</h2>
                    <p class="dash-card-sub">Menampilkan maksimal 5 transaksi peminjaman terakhir Anda.</p>
                </div>
                <a href="my-loans.php" class="btn btn-secondary btn-sm">
                    Lihat Semua &rarr;
                </a>
            </div>

            <?php if (empty($recentTransactions)): ?>
                <div class="empty-state">
                    <span class="empty-state-icon" aria-hidden="true">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                        </svg>
                    </span>
                    <h3>Belum Ada Riwayat Peminjaman</h3>
                    <p>Anda belum pernah meminjam e-book dari koleksi kami.</p>
                    <a href="index.php#katalog" class="btn btn-primary">
                        Jelajahi Koleksi Sekarang
                    </a>
                </div>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="data-table" aria-label="Tabel aktivitas peminjaman terbaru">
                        <thead>
                            <tr>
                                <th scope="col">No</th>
                                <th scope="col">Judul E-Book</th>
                                <th scope="col">Tanggal Pinjam</th>
                                <th scope="col">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentTransactions as $idx => $t): ?>
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
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div style="margin-top: var(--s-5); display: flex; gap: var(--s-3); flex-wrap: wrap;">
                    <a href="my-loans.php" class="btn btn-secondary btn-sm">
                        Buka Riwayat Peminjaman Penuh
                    </a>
                    <a href="index.php#katalog" class="btn btn-primary btn-sm">
                        Pinjam E-Book Baru
                    </a>
                </div>
            <?php endif; ?>
        </section>

        <!-- ACCOUNT PROFILE CARD -->
        <section class="dash-card" aria-labelledby="accountInfoTitle">
            <div class="dash-card-header">
                <div>
                    <h2 class="dash-card-title" id="accountInfoTitle">Informasi Akun</h2>
                    <p class="dash-card-sub">Detail profil keanggotaan terdaftar.</p>
                </div>
            </div>

            <div class="account-grid">
                <div class="account-field">
                    <div class="account-field-label">ID Anggota</div>
                    <div class="account-field-value">#<?= htmlspecialchars((string) $user['id']) ?></div>
                </div>
                <div class="account-field">
                    <div class="account-field-label">Nama Lengkap</div>
                    <div class="account-field-value"><?= htmlspecialchars($user['name']) ?></div>
                </div>
                <div class="account-field">
                    <div class="account-field-label">Alamat Email</div>
                    <div class="account-field-value"><?= htmlspecialchars($user['email']) ?></div>
                </div>
                <div class="account-field">
                    <div class="account-field-label">Peran Pengguna</div>
                    <div class="account-field-value">
                        <span class="badge badge-primary"><?= htmlspecialchars($user['role']) ?></span>
                    </div>
                </div>
                <?php if ($memberObject): ?>
                    <div class="account-field" style="grid-column: 1 / -1;">
                        <div class="account-field-label">Tanggal Bergabung</div>
                        <div class="account-field-value"><?= htmlspecialchars($memberObject->getRegistrationDate()) ?></div>
                    </div>
                <?php endif; ?>
            </div>

            <div style="margin-top: var(--s-6); display: flex; gap: var(--s-3); justify-content: flex-end; align-items: center;">
                <form method="POST">
                    <button
                        type="submit"
                        name="logout"
                        value="1"
                        class="btn btn-danger btn-sm"
                        onclick="return confirm('Apakah Anda yakin ingin keluar dari akun?')"
                    >
                        Keluar dari Akun
                    </button>
                </form>
            </div>
        </section>

    </main>

    <?php require_once __DIR__ . '/templates/footer.php'; ?>

</body>
</html>
