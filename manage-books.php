<?php

require_once __DIR__ . '/config/bootstrap.php';

use App\Models\Book;
use App\Services\BookService;

$service = new BookService();
$message = "";

// PROSES TAMBAH BUKU
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    if ($action === 'add') {

        $books = $service->getAll();

        $ids = array_map(
            fn($book) => $book->getId(),
            $books
        );

        $newId = empty($ids) ? 1 : max($ids) + 1;

        $title = trim($_POST['title'] ?? '');
        $author = trim($_POST['author'] ?? '');
        $category = trim($_POST['category'] ?? '');

        if ($title !== '' && $author !== '' && $category !== '') {

            $book = new Book(
                $newId,
                $title,
                $author,
                $category,
                'Tersedia'
            );

            $service->add($book);

            header('Location: manage-books.php?status=added');
            exit;

        }
    }

    // PROSES EDIT BUKU
    if ($action === 'update') {

        $id = (int) ($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $author = trim($_POST['author'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $status = $_POST['status'] ?? '';

        if (
            $id > 0 &&
            $title !== '' &&
            $author !== '' &&
            $category !== '' &&
            in_array($status, ['Tersedia', 'Dipinjam'], true)
        ) {
            $book = new Book(
                $id,
                $title,
                $author,
                $category,
                $status
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

// MENGAMBIL DATA BUKU
$books = $service->getAll();

// MENGAMBIL DATA BUKU UNTUK DIEDIT
$editBook = null;

if (isset($_GET['edit'])) {
    $editBook = $service->findById((int) $_GET['edit']);
}

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Kelola Buku - Digital Library</title>

    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        .manage-section {
            margin: 40px 0;
        }

        .manage-card {
            background: white;
            padding: 25px;
            border-radius: 14px;
            border: 1px solid #eeeaf5;
            margin-bottom: 25px;
        }

        .manage-card input,
        .manage-card select {
            width: 100%;
            padding: 12px;
            margin: 8px 0 16px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }

        .book-table {
            width: 100%;
            border-collapse: collapse;
        }

        .book-table th,
        .book-table td {
            padding: 13px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .book-table th {
            background: #f0eaff;
            color: #7041c8;
        }

        .action-link {
            color: #7041c8;
            text-decoration: none;
            margin-right: 8px;
        }

        .delete-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 9px 12px;
            border-radius: 7px;
            cursor: pointer;
        }

        .message {
            background: #e5f8eb;
            color: #18743a;
            padding: 13px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .table-wrapper {
            overflow-x: auto;
        }
    </style>
</head>

<body>

    <header class="navbar">
        <div class="container nav-content">

            <div class="logo">📚 Digital Library</div>

            <nav class="nav-links">
                <a href="index.php">Beranda</a>
                <a href="manage-books.php">Kelola Buku</a>
            </nav>

        </div>
    </header>

    <main class="container manage-section">

        <h1>Pengelolaan E-book</h1>

        <p style="color: #817b91; margin: 10px 0 25px;">
            Tambahkan, edit, dan hapus koleksi buku perpustakaan.
        </p>

        <?php if (isset($_GET['status'])): ?>

            <div class="message">
                <?php
                $messages = [
                    'added' => 'Buku berhasil ditambahkan!',
                    'updated' => 'Data buku berhasil diperbarui!',
                    'deleted' => 'Buku berhasil dihapus!'
                ];

                echo $messages[$_GET['status']] ?? 'Operasi selesai.';
                ?>
            </div>

        <?php endif; ?>


        <!-- FORM TAMBAH DAN EDIT -->

        <section class="manage-card">

            <h2>
                <?= $editBook ? 'Edit Buku' : 'Tambah Buku Baru' ?>
            </h2>

            <form method="POST" action="manage-books.php">

                <input
                    type="hidden"
                    name="action"
                    value="<?= $editBook ? 'update' : 'add' ?>"
                >

                <?php if ($editBook): ?>

                    <input
                        type="hidden"
                        name="id"
                        value="<?= $editBook->getId() ?>"
                    >

                <?php endif; ?>

                <label>Judul Buku</label>

                <input
                    type="text"
                    name="title"
                    placeholder="Masukkan judul buku"
                    value="<?= htmlspecialchars($editBook?->getTitle() ?? '') ?>"
                    required
                >

                <label>Nama Penulis</label>

                <input
                    type="text"
                    name="author"
                    placeholder="Masukkan nama penulis"
                    value="<?= htmlspecialchars($editBook?->getAuthor() ?? '') ?>"
                    required
                >

                <label>Kategori</label>

                <input
                    type="text"
                    name="category"
                    placeholder="Contoh: Programming"
                    value="<?= htmlspecialchars($editBook?->getCategory() ?? '') ?>"
                    required
                >

                <?php if ($editBook): ?>

                    <label>Status Buku</label>

                    <select name="status">
                        <option value="Tersedia"
                            <?= $editBook->getStatus() === 'Tersedia' ? 'selected' : '' ?>>
                            Tersedia
                        </option>

                        <option value="Dipinjam"
                            <?= $editBook->getStatus() === 'Dipinjam' ? 'selected' : '' ?>>
                            Dipinjam
                        </option>
                    </select>

                <?php endif; ?>

                <button type="submit" class="btn">
                    <?= $editBook ? 'Simpan Perubahan' : 'Tambah Buku' ?>
                </button>

                <?php if ($editBook): ?>

                    <a href="manage-books.php" class="action-link">
                        Batal Edit
                    </a>

                <?php endif; ?>

            </form>

        </section>


        <!-- DAFTAR BUKU -->

        <section class="manage-card">

            <h2>Daftar Koleksi Buku</h2>

            <p style="margin: 10px 0 20px; color: #817b91;">
                Total koleksi: <?= count($books) ?> buku
            </p>

            <div class="table-wrapper">

                <table class="book-table">

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

                        <?php foreach ($books as $book): ?>

                            <tr>
                                <td><?= $book->getId() ?></td>

                                <td>
                                    <?= htmlspecialchars($book->getTitle()) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($book->getAuthor()) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($book->getCategory()) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($book->getStatus()) ?>
                                </td>

                                <td>
                                    <a
                                        class="action-link"
                                        href="manage-books.php?edit=<?= $book->getId() ?>"
                                    >
                                        Edit
                                    </a>

                                    <form
                                        method="POST"
                                        action="manage-books.php"
                                        style="display:inline;"
                                        onsubmit="return confirm('Yakin ingin menghapus buku ini?')"
                                    >

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="delete"
                                        >

                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?= $book->getId() ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="delete-btn"
                                        >
                                            Hapus
                                        </button>

                                    </form>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                        <?php if (empty($books)): ?>

                            <tr>
                                <td colspan="6">
                                    Belum ada buku tersedia.
                                </td>
                            </tr>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </section>

    </main>

    <footer>
        <p>&copy; 2026 Digital Library. All Rights Reserved.</p>
    </footer>

</body>
</html>
