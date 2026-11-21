<?php

require_once __DIR__ . '/config/bootstrap.php';

use App\Models\Book;
use App\Services\BookService;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// WAJIB LOGIN — Halaman ini memerlukan autentikasi
if (!isset($_SESSION['user'])) {
    $_SESSION['flash']      = 'Silakan login terlebih dahulu untuk mengakses halaman Kelola Buku.';
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

        $title    = trim($_POST['title'] ?? '');
        $author   = trim($_POST['author'] ?? '');
        $category = trim($_POST['category'] ?? '');

        if ($title !== '' && $author !== '' && $category !== '') {

            $book = new Book($newId, $title, $author, $category, 'Tersedia');
            $service->add($book);

            header('Location: manage-books.php?status=added');
            exit;
        }
    }

    // PROSES EDIT BUKU
    if ($action === 'update') {

        $id       = (int) ($_POST['id'] ?? 0);
        $title    = trim($_POST['title'] ?? '');
        $author   = trim($_POST['author'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $status   = $_POST['status'] ?? '';

        if (
            $id > 0 &&
            $title !== '' &&
            $author !== '' &&
            $category !== '' &&
            in_array($status, ['Tersedia', 'Dipinjam'], true)
        ) {
            $book = new Book($id, $title, $author, $category, $status);
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

// MENGAMBIL DATA BUKU
$books = $service->getAll();

// MENGAMBIL DATA BUKU UNTUK DIEDIT
$editBook = null;
if (isset($_GET['edit'])) {
    $editBook = $service->findById((int) $_GET['edit']);
}

// Pesan status operasi
$statusMessages = [
    'added'   => 'Buku berhasil ditambahkan ke koleksi!',
    'updated' => 'Data buku berhasil diperbarui!',
    'deleted' => 'Buku berhasil dihapus dari koleksi.',
];
$statusParam = $_GET['status'] ?? '';

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Buku - Digital Library</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

    <?php require_once __DIR__ . '/templates/navbar.php'; ?>

    <main class="container manage-section">

        <h1>Pengelolaan E-book</h1>
        <p style="color: var(--color-text-light); margin: 10px 0 25px;">
            Tambahkan, edit, dan hapus koleksi buku perpustakaan.
            Login sebagai: <strong><?= htmlspecialchars($user['name']) ?></strong>
        </p>

        <!-- PESAN STATUS OPERASI -->
        <?php if ($statusParam && isset($statusMessages[$statusParam])): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($statusMessages[$statusParam]) ?>
            </div>
        <?php endif; ?>


        <!-- FORM TAMBAH DAN EDIT -->
        <section class="manage-card">

            <h2><?= $editBook ? 'Edit Buku' : 'Tambah Buku Baru' ?></h2>
            <p style="color: var(--color-text-light); font-size: 14px; margin-bottom: 20px;">
                <?= $editBook
                    ? 'Ubah data buku yang dipilih.'
                    : 'Isi formulir di bawah untuk menambahkan buku baru ke katalog.' ?>
            </p>

            <form method="POST" action="manage-books.php" class="manage-form">

                <input type="hidden" name="action" value="<?= $editBook ? 'update' : 'add' ?>">

                <?php if ($editBook): ?>
                    <input type="hidden" name="id" value="<?= $editBook->getId() ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="title">Judul Buku</label>
                    <input
                        type="text"
                        name="title"
                        id="title"
                        placeholder="Masukkan judul buku"
                        value="<?= htmlspecialchars($editBook?->getTitle() ?? '') ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="author">Nama Penulis</label>
                    <input
                        type="text"
                        name="author"
                        id="author"
                        placeholder="Masukkan nama penulis"
                        value="<?= htmlspecialchars($editBook?->getAuthor() ?? '') ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="category">Kategori</label>
                    <input
                        type="text"
                        name="category"
                        id="category"
                        placeholder="Contoh: Programming, Design, AI"
                        value="<?= htmlspecialchars($editBook?->getCategory() ?? '') ?>"
                        required
                    >
                </div>

                <?php if ($editBook): ?>
                    <div class="form-group">
                        <label for="status">Status Buku</label>
                        <select name="status" id="status">
                            <option value="Tersedia" <?= $editBook->getStatus() === 'Tersedia' ? 'selected' : '' ?>>
                                Tersedia
                            </option>
                            <option value="Dipinjam" <?= $editBook->getStatus() === 'Dipinjam' ? 'selected' : '' ?>>
                                Dipinjam
                            </option>
                        </select>
                    </div>
                <?php endif; ?>

                <div class="form-actions">
                    <button type="submit" class="btn">
                        <?= $editBook ? '💾 Simpan Perubahan' : '➕ Tambah Buku' ?>
                    </button>

                    <?php if ($editBook): ?>
                        <a href="manage-books.php" class="action-link">✕ Batal Edit</a>
                    <?php endif; ?>
                </div>

            </form>

        </section>


        <!-- DAFTAR BUKU -->
        <section class="manage-card">

            <h2>Daftar Koleksi Buku</h2>
            <p style="color: var(--color-text-light); font-size: 14px; margin-bottom: 20px;">
                Total koleksi: <strong><?= count($books) ?></strong> buku
            </p>

            <div class="table-wrapper">

                <table class="data-table" aria-label="Daftar koleksi buku">

                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Judul</th>
                            <th>Penulis</th>
                            <th>Kategori</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php if (empty($books)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; color: var(--color-text-light); padding: 30px;">
                                    Belum ada buku tersedia.
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($books as $book): ?>
                            <tr>
                                <td><?= $book->getId() ?></td>

                                <td><?= htmlspecialchars($book->getTitle()) ?></td>

                                <td><?= htmlspecialchars($book->getAuthor()) ?></td>

                                <td><?= htmlspecialchars($book->getCategory()) ?></td>

                                <td>
                                    <?php if ($book->getStatus() === 'Tersedia'): ?>
                                        <span class="status-returned">Tersedia</span>
                                    <?php else: ?>
                                        <span class="status-borrowed">Dipinjam</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <a
                                        class="action-link"
                                        href="manage-books.php?edit=<?= $book->getId() ?>"
                                        aria-label="Edit buku <?= htmlspecialchars($book->getTitle()) ?>"
                                    >
                                        ✏️ Edit
                                    </a>

                                    <form
                                        method="POST"
                                        action="manage-books.php"
                                        style="display:inline;"
                                        onsubmit="return confirm('Yakin ingin menghapus buku ini?\n\n<?= htmlspecialchars($book->getTitle()) ?>')"
                                    >
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $book->getId() ?>">

                                        <button
                                            type="submit"
                                            class="delete-btn"
                                            aria-label="Hapus buku <?= htmlspecialchars($book->getTitle()) ?>"
                                        >
                                            🗑 Hapus
                                        </button>
                                    </form>
                                </td>

                            </tr>
                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        </section>

    </main>

    <?php require_once __DIR__ . '/templates/footer.php'; ?>

</body>
</html>
