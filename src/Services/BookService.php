<?php

namespace App\Services;

use App\Models\Book;
use App\Contracts\BookRepositoryInterface;

/**
 * BookService — Implementasi nyata dari BookRepositoryInterface.
 * Mengelola penyimpanan books.json dengan thread-safety (LOCK_EX),
 * validasi duplikasi, dan integrasi impor dari Google Books API.
 */
class BookService implements BookRepositoryInterface
{
    private string $filePath;

    public function __construct()
    {
        $this->filePath = __DIR__ . '/../../data/books.json';
    }

    /**
     * Membaca semua data buku dari books.json
     * @return Book[]
     */
    public function getAll(): array
    {
        if (!file_exists($this->filePath)) {
            return [];
        }

        $data = file_get_contents($this->filePath);
        $books = json_decode((string) $data, true);

        if (!is_array($books)) {
            return [];
        }

        $result = [];

        foreach ($books as $item) {
            $result[] = new Book(
                (int) $item['id'],
                (string) $item['title'],
                (string) $item['author'],
                (string) $item['category'],
                (string) ($item['status'] ?? 'Tersedia'),
                (string) ($item['coverImage'] ?? ''),
                (string) ($item['description'] ?? ''),
                (string) ($item['isbn'] ?? ''),
                (string) ($item['googleBooksId'] ?? ''),
                (string) ($item['publishedYear'] ?? ''),
                (int) ($item['pageCount'] ?? 0),
                (string) ($item['publisher'] ?? '')
            );
        }

        return $result;
    }

    /**
     * Menyimpan kumpulan data buku ke JSON dengan file-lock eksklusif
     */
    private function save(array $books): bool
    {
        $data = [];

        foreach ($books as $book) {
            $data[] = $book->toArray();
        }

        $json = json_encode(
            $data,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        if ($json === false) {
            return false;
        }

        return file_put_contents(
            $this->filePath,
            $json,
            LOCK_EX
        ) !== false;
    }

    /**
     * Menambahkan buku baru ke lokal
     */
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

    /**
     * Mencari buku berdasarkan ID numerik
     */
    public function findById(int $id): ?Book
    {
        foreach ($this->getAll() as $book) {
            if ($book->getId() === $id) {
                return $book;
            }
        }

        return null;
    }

    /**
     * Mengubah data buku
     */
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

    /**
     * Menghapus buku berdasarkan ID
     */
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

    /**
     * Mencari buku lokal berdasarkan Google Books Volume ID
     */
    public function findByGoogleBooksId(string $gbId): ?Book
    {
        $gbId = trim($gbId);
        if ($gbId === '') {
            return null;
        }

        foreach ($this->getAll() as $book) {
            if ($book->getGoogleBooksId() !== '' && $book->getGoogleBooksId() === $gbId) {
                return $book;
            }
        }

        return null;
    }

    /**
     * Mencari buku lokal berdasarkan ISBN
     */
    public function findByIsbn(string $isbn): ?Book
    {
        $isbn = preg_replace('/[^0-9X]/i', '', $isbn);
        if ($isbn === '') {
            return null;
        }

        foreach ($this->getAll() as $book) {
            $bookIsbn = preg_replace('/[^0-9X]/i', '', $book->getIsbn());
            if ($bookIsbn !== '' && $bookIsbn === $isbn) {
                return $book;
            }
        }

        return null;
    }

    /**
     * Memeriksa apakah buku sudah tersedia di books.json
     */
    public function isDuplicate(string $title, string $author, string $isbn = '', string $googleBooksId = ''): bool
    {
        // 1. Cek berdasarkan Google Books ID
        if ($googleBooksId !== '' && $this->findByGoogleBooksId($googleBooksId) !== null) {
            return true;
        }

        // 2. Cek berdasarkan ISBN
        if ($isbn !== '' && $this->findByIsbn($isbn) !== null) {
            return true;
        }

        // 3. Cek kombinasi judul & penulis
        $cleanTitle = strtolower(trim($title));
        $cleanAuthor = strtolower(trim($author));

        foreach ($this->getAll() as $book) {
            if (
                strtolower(trim($book->getTitle())) === $cleanTitle &&
                strtolower(trim($book->getAuthor())) === $cleanAuthor
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Mengimpor buku dari data mapped Google Books ke books.json
     *
     * @param array $mappedData Data hasil BookMapper::mapVolume()
     * @return array ['success' => bool, 'message' => string, 'book' => ?Book]
     */
    public function importFromGoogleBooks(array $mappedData): array
    {
        $title = trim($mappedData['title'] ?? '');
        $author = trim($mappedData['author'] ?? 'Penulis Tidak Diketahui');
        $category = trim($mappedData['category'] ?? 'Umum');
        $isbn = trim($mappedData['isbn'] ?? '');
        $googleBooksId = trim($mappedData['googleBooksId'] ?? ($mappedData['id'] ?? ''));

        if ($title === '') {
            return [
                'success' => false,
                'message' => 'Gagal mengimpor: data judul buku tidak ditemukan.',
                'book' => null
            ];
        }

        // Cek duplikasi
        if ($this->isDuplicate($title, $author, $isbn, $googleBooksId)) {
            return [
                'success' => false,
                'message' => "Buku '{$title}' sudah tersedia di dalam katalog perpustakaan lokal.",
                'book' => null,
                'duplicate' => true
            ];
        }

        // Tentukan ID numerik lokal berikutnya
        $books = $this->getAll();
        $ids = array_map(fn($b) => $b->getId(), $books);
        $nextId = empty($ids) ? 1 : max($ids) + 1;

        // Buat objek Book baru
        $newBook = new Book(
            $nextId,
            $title,
            $author,
            $category,
            'Tersedia',
            (string) ($mappedData['coverImage'] ?? ''),
            (string) ($mappedData['description'] ?? ''),
            $isbn,
            $googleBooksId,
            (string) ($mappedData['publishedYear'] ?? ''),
            (int) ($mappedData['pageCount'] ?? 0),
            (string) ($mappedData['publisher'] ?? '')
        );

        $saved = $this->add($newBook);

        if ($saved) {
            return [
                'success' => true,
                'message' => "Buku '{$title}' berhasil diimpor ke katalog lokal perpustakaan!",
                'book' => $newBook
            ];
        }

        return [
            'success' => false,
            'message' => 'Gagal menyimpan data ke storage books.json.',
            'book' => null
        ];
    }
}
