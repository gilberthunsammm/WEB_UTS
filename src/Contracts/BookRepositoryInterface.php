<?php

namespace App\Contracts;

use App\Models\Book;

/**
 * Interface BookRepositoryInterface
 * Mendefinisikan kontrak operasi katalog buku.
 * Memungkinkan implementasi storage yang berbeda (JSON, XML, Database)
 * tanpa mengubah kode yang bergantung padanya. (Polymorphism via Interface)
 */
interface BookRepositoryInterface
{
    /**
     * Mengambil seluruh koleksi buku.
     * @return Book[]
     */
    public function getAll(): array;

    /**
     * Mencari satu buku berdasarkan ID.
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
}
