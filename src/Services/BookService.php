<?php

namespace App\Services;

use App\Models\Book;

class BookService
{
    private string $filePath;

    public function __construct()
    {
        $this->filePath = __DIR__ . '/../../data/books.json';
    }

    // Membaca semua data buku
    public function getAll(): array
    {
        if (!file_exists($this->filePath)) {
            return [];
        }

        $data = file_get_contents($this->filePath);

        $books = json_decode($data, true);

        if (!is_array($books)) {
            return [];
        }

        $result = [];

        foreach ($books as $item) {
            $result[] = new Book(
                $item['id'],
                $item['title'],
                $item['author'],
                $item['category'],
                $item['status']
            );
        }

        return $result;
    }

    // Menyimpan data buku ke JSON
    private function save(array $books): bool
    {
        $data = [];

        foreach ($books as $book) {
            $data[] = $book->toArray();
        }

        $json = json_encode(
            $data,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
        );

        return file_put_contents(
            $this->filePath,
            $json,
            LOCK_EX
        ) !== false;
    }

    // Menambahkan buku
    public function add(Book $newBook): bool
    {
        $books = $this->getAll();

        foreach ($books as $book) {
            if ($book->getId() === $newBook->getId()) {
                return false;
            }
        }

        $books[] = $newBook;

        return $this->save($books);
    }

    // Mencari buku berdasarkan ID
    public function findById(int $id): ?Book
    {
        foreach ($this->getAll() as $book) {
            if ($book->getId() === $id) {
                return $book;
            }
        }

        return null;
    }

    // Mengubah data buku
    public function update(Book $updatedBook): bool
    {
        $books = $this->getAll();
        $found = false;

        foreach ($books as $index => $book) {
            if ($book->getId() === $updatedBook->getId()) {
                $books[$index] = $updatedBook;
                $found = true;
                break;
            }
        }

        return $found && $this->save($books);
    }

    // Menghapus buku
    public function delete(int $id): bool
    {
        $books = $this->getAll();

        $filtered = array_filter(
            $books,
            fn($book) => $book->getId() !== $id
        );

        if (count($filtered) === count($books)) {
            return false;
        }

        return $this->save(array_values($filtered));
    }
}
