<?php

require_once __DIR__ . '/config/bootstrap.php';

use App\Models\Book;
use App\Services\BookService;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Autentikasi Pengguna
if (!isset($_SESSION['user'])) {
    $_SESSION['flash']      = 'Silakan login terlebih dahulu untuk mengakses menu pengelolaan buku.';
    $_SESSION['flash_type'] = 'warning';
    header('Location: login.php');
    exit;
}

$user    = $_SESSION['user'];
$service = new BookService();

// PROSES TAMBAH BUKU
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $books = $service->getAll();
        $ids   = array_map(fn($book) => $book->getId(), $books);
        $newId = empty($ids) ? 1 : max($ids) + 1;

        $title      = trim($_POST['title'] ?? '');
        $author     = trim($_POST['author'] ?? '');
        $category   = trim($_POST['category'] ?? '');
        $coverImage = trim($_POST['coverImage'] ?? '');
        $isbn       = trim($_POST['isbn'] ?? '');

        if ($title !== '' && $author !== '' && $category !== '') {
            $book = new Book(
                $newId,
                $title,
                $author,
                $category,
                'Tersedia',
                $coverImage,
                '',
                $isbn
            );
            $service->add($book);
            header('Location: manage-books.php?status=added');
            exit;
        }
    }

    // PROSES EDIT BUKU
    if ($action === 'update') {
        $id         = (int) ($_POST['id'] ?? 0);
        $title      = trim($_POST['title'] ?? '');
        $author     = trim($_POST['author'] ?? '');
        $category   = trim($_POST['category'] ?? '');
        $status     = $_POST['status'] ?? '';
        $coverImage = trim($_POST['coverImage'] ?? '');
        $isbn       = trim($_POST['isbn'] ?? '');

        if (
            $id > 0 &&
            $title !== '' &&
            $author !== '' &&
            $category !== '' &&
            in_array($status, ['Tersedia', 'Dipinjam'], true)
        ) {
            $existing = $service->findById($id);
            $desc     = $existing ? $existing->getDescription() : '';
            $gbId     = $existing ? $existing->getGoogleBooksId() : '';
            $year     = $existing ? $existing->getPublishedYear() : '';
            $pages    = $existing ? $existing->getPageCount() : 0;
            $pub      = $existing ? $existing->getPublisher() : '';

            $book = new Book(
                $id,
                $title,
                $author,
                $category,
                $status,
                $coverImage,
                $desc,
                $isbn,
                $gbId,
                $year,
                $pages,
                $pub
            );
            $service->update($book);
            header('Location: manage-books.php?status=updated');
            exit;
        }
    }

    // PROSES HAPUS BUKU
    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $service->delete($id);
            header('Location: manage-books.php?status=deleted');
            exit;
        }
    }
}

$books = $service->getAll();

$editBook = null;
if (isset($_GET['edit'])) {
    $editBook = $service->findById((int) $_GET['edit']);
}

$statusMessages = [
    'added'   => 'E-book baru berhasil ditambahkan ke katalog perpustakaan!',
    'updated' => 'Perubahan informasi e-book berhasil disimpan!',
    'deleted' => 'E-book telah berhasil dihapus dari koleksi.',
];
$statusParam = $_GET['status'] ?? '';

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Koleksi E-Book — Digital Library</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body class="page-wrapper">

    <?php require_once __DIR__ . '/templates/navbar.php'; ?>

    <main class="container manage-layout">

        <div class="page-title-bar">
            <div>
                <div class="section-eyebrow">Manajemen Pustaka</div>
                <h1 class="serif">Pengelolaan Koleksi E-Book</h1>
                <p>Tambah judul baru, perbarui metadata pustaka, atau impor dari Google Books.</p>
            </div>
            <div style="display: flex; gap: var(--s-3); flex-wrap: wrap;">
                <a href="discover.php" class="btn btn-primary btn-sm">
                    + Impor dari Google Books
                </a>
                <a href="index.php#katalog" class="btn btn-secondary btn-sm">
                    &larr; Lihat Katalog Publik
                </a>
            </div>
        </div>

        <?php if ($statusParam && isset($statusMessages[$statusParam])): ?>
            <div class="alert alert-success" data-auto-dismiss role="status">
                <span class="alert-icon">✓</span>
                <span><?= htmlspecialchars($statusMessages[$statusParam]) ?></span>
            </div>
        <?php endif; ?>

        <div class="manage-grid">

            <!-- FORM CARD (TAMBAH / EDIT) -->
            <section class="manage-form-card" aria-labelledby="formTitle">
                <div class="manage-card-header">
                    <h2 id="formTitle"><?= $editBook ? 'Perbarui Data Buku' : 'Tambah E-Book Baru' ?></h2>
                    <p><?= $editBook ? 'Sunting informasi buku yang dipilih.' : 'Lengkapi atribut buku untuk katalog lokal.' ?></p>
                </div>
                <div class="manage-card-body">
                    <form method="POST" action="manage-books.php" class="manage-form">
                        <input type="hidden" name="action" value="<?= $editBook ? 'update' : 'add' ?>">
                        <?php if ($editBook): ?>
                            <input type="hidden" name="id" value="<?= $editBook->getId() ?>">
                        <?php endif; ?>

                        <div class="form-group">
                            <label for="bookTitle" class="form-label">Judul E-Book</label>
                            <input
                                type="text"
                                name="title"
                                id="bookTitle"
                                class="form-input"
                                placeholder="Contoh: Arsitektur Komputer Modern"
                                value="<?= htmlspecialchars($editBook?->getTitle() ?? '') ?>"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label for="bookAuthor" class="form-label">Nama Penulis</label>
                            <input
                                type="text"
                                name="author"
                                id="bookAuthor"
                                class="form-input"
                                placeholder="Contoh: Dr. Budi Wicaksono"
                                value="<?= htmlspecialchars($editBook?->getAuthor() ?? '') ?>"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label for="bookCategory" class="form-label">Kategori / Bidang Ilmu</label>
                            <input
                                type="text"
                                name="category"
                                id="bookCategory"
                                class="form-input"
                                placeholder="Contoh: Programming, AI, UI/UX"
                                value="<?= htmlspecialchars($editBook?->getCategory() ?? '') ?>"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label for="bookCover" class="form-label">URL Cover Gambar (Opsional)</label>
                            <input
                                type="url"
                                name="coverImage"
                                id="bookCover"
                                class="form-input"
                                placeholder="https://books.google.com/..."
                                value="<?= htmlspecialchars($editBook?->getCoverImage() ?? '') ?>"
                            >
                        </div>

                        <div class="form-group">
                            <label for="bookIsbn" class="form-label">ISBN (Opsional)</label>
                            <input
                                type="text"
                                name="isbn"
                                id="bookIsbn"
                                class="form-input"
                                placeholder="Contoh: 9780135956977"
                                value="<?= htmlspecialchars($editBook?->getIsbn() ?? '') ?>"
                            >
                        </div>

                        <?php if ($editBook): ?>
                            <div class="form-group">
                                <label for="bookStatus" class="form-label">Status Ketersediaan</label>
                                <select name="status" id="bookStatus" class="form-input">
                                    <option value="Tersedia" <?= $editBook->getStatus() === 'Tersedia' ? 'selected' : '' ?>>
                                        Tersedia
                                    </option>
                                    <option value="Dipinjam" <?= $editBook->getStatus() === 'Dipinjam' ? 'selected' : '' ?>>
                                        Dipinjam
                                    </option>
                                </select>
                            </div>
                        <?php endif; ?>

                        <div class="manage-form-actions">
                            <button type="submit" class="btn btn-primary btn-full">
                                <?= $editBook ? 'Simpan Perubahan' : 'Tambah ke Koleksi' ?>
                            </button>
                            <?php if ($editBook): ?>
                                <a href="manage-books.php" class="btn btn-secondary btn-full">
                                    Batal Mengedit
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </section>

            <!-- TABLE CARD (DAFTAR BUKU) -->
            <section class="manage-table-card" aria-labelledby="tableTitle">
                <div class="manage-card-header">
                    <h2 id="tableTitle">Inventaris E-Book (<?= count($books) ?>)</h2>
                    <p>Daftar seluruh buku yang terdaftar di repositori lokal perpustakaan.</p>
                </div>
                <div class="table-wrap">
                    <table class="data-table" aria-label="Tabel inventaris e-book">
                        <thead>
                            <tr>
                                <th scope="col">ID</th>
                                <th scope="col">Buku</th>
                                <th scope="col">Kategori</th>
                                <th scope="col">Status</th>
                                <th scope="col" style="text-align: right;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($books)): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; color: var(--text-3); padding: var(--s-10);">
                                        Belum ada data buku dalam sistem.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($books as $b): ?>
                                    <tr>
                                        <td><code>#<?= $b->getId() ?></code></td>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: var(--s-3);">
                                                <?php if ($b->getCoverImage() !== ''): ?>
                                                    <img
                                                        src="<?= htmlspecialchars($b->getCoverImage()) ?>"
                                                        alt="Cover"
                                                        style="width: 32px; height: 44px; object-fit: cover; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,.15); flex-shrink: 0;"
                                                    >
                                                <?php endif; ?>
                                                <div>
                                                    <div style="font-weight: 600; color: var(--text-1);">
                                                        <?= htmlspecialchars($b->getTitle()) ?>
                                                    </div>
                                                    <div style="font-size: var(--text-xs); color: var(--text-3);">
                                                        <?= htmlspecialchars($b->getAuthor()) ?>
                                                        <?php if ($b->getPublishedYear() !== ''): ?>
                                                            &bull; <?= htmlspecialchars($b->getPublishedYear()) ?>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge badge-primary">
                                                <?= htmlspecialchars($b->getCategory()) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($b->getStatus() === 'Tersedia'): ?>
                                                <span class="badge badge-available">Tersedia</span>
                                            <?php else: ?>
                                                <span class="badge badge-borrowed">Dipinjam</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align: right; white-space: nowrap;">
                                            <a
                                                href="manage-books.php?edit=<?= $b->getId() ?>"
                                                class="btn-link"
                                                style="color: var(--accent); margin-right: var(--s-2);"
                                                aria-label="Edit buku <?= htmlspecialchars($b->getTitle()) ?>"
                                            >
                                                Edit
                                            </a>
                                            <form
                                                method="POST"
                                                action="manage-books.php"
                                                style="display: inline;"
                                                onsubmit="return confirm('Apakah Anda yakin ingin menghapus buku ini dari katalog?\n\nJudul: <?= htmlspecialchars($b->getTitle()) ?>')"
                                            >
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= $b->getId() ?>">
                                                <button
                                                    type="submit"
                                                    class="btn-link"
                                                    style="color: var(--danger);"
                                                    aria-label="Hapus buku <?= htmlspecialchars($b->getTitle()) ?>"
                                                >
                                                    Hapus
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

        </div>

    </main>

    <?php require_once __DIR__ . '/templates/footer.php'; ?>

</body>
</html>
