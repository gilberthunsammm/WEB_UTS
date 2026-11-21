<?php

namespace App\Contracts;

use App\Models\Book;

/**
 * Interface BookRepositoryInterface
 * Mendefinisikan kontrak operasi katalog buku lokal.
 * Menerapkan prinsip Polymorphism via Interface sehingga implementasi storage
 * (JSON, XML, Database) dapat dipertukarkan tanpa mengubah layer aplikasi di atasnya.
 */
interface BookRepositoryInterface
{
    /**
     * Mengambil seluruh koleksi buku lokal.
     * @return Book[]
     */
    public function getAll(): array;

    /**
     * Mencari satu buku berdasarkan ID numerik lokal.
     */
    public function findById(int $id): ?Book;

    /**
     * Menambahkan buku baru ke dalam storage.
     */
    public function add(Book $newBook): bool;

    /**
     * Memperbarui data buku yang sudah ada.
     */
    public function update(Book $updatedBook): bool;

    /**
     * Menghapus buku berdasarkan ID.
     */
    public function delete(int $id): bool;

    /**
     * Mencari buku berdasarkan Google Books Volume ID.
     */
    public function findByGoogleBooksId(string $gbId): ?Book;

    /**
     * Mencari buku berdasarkan ISBN.
     */
    public function findByIsbn(string $isbn): ?Book;

    /**
     * Memeriksa apakah buku sudah pernah tersimpan di katalog lokal (mencegah duplikasi).
     */
    public function isDuplicate(string $title, string $author, string $isbn = '', string $googleBooksId = ''): bool;

    /**
     * Mengimpor buku hasil Google Books API ke storage lokal books.json.
     * @return array ['success' => bool, 'message' => string, 'book' => ?Book]
     */
    public function importFromGoogleBooks(array $mappedData): array;
}
