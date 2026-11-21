<?php

namespace App\Services;

use App\Models\Transaction;
use App\Services\BookService;

class TransactionService
{
    private string $filePath;
    private BookService $bookService;

    public function __construct()
    {
        $this->filePath = __DIR__ . '/../../data/transactions.json';
        $this->bookService = new BookService();
    }

    // Mengambil semua transaksi
    public function getAll(): array
    {
        if (!file_exists($this->filePath)) {
            return [];
        }

        $data = file_get_contents($this->filePath);
        $transactions = json_decode($data, true);

        if (!is_array($transactions)) {
            return [];
        }

        $result = [];

        foreach ($transactions as $item) {
            $result[] = new Transaction(
                $item['id'],
                $item['memberId'],
                $item['bookId'],
                $item['bookTitle'],
                $item['borrowDate'],
                $item['status']
            );
        }

        return $result;
    }

    // Menyimpan transaksi ke JSON
    private function save(array $transactions): bool
    {
        $data = [];

        foreach ($transactions as $transaction) {
            $data[] = $transaction->toArray();
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

    // Meminjam buku
    public function borrowBook(
        int $memberId,
        int $bookId
    ): bool {

        // Mencari buku
        $book = $this->bookService->findById($bookId);

        if ($book === null) {
            return false;
        }

        // Memastikan buku tersedia
        if ($book->getStatus() !== "Tersedia") {
            return false;
        }

        $transactions = $this->getAll();

        // Membuat ID transaksi otomatis
        $newId = 1;

        foreach ($transactions as $transaction) {
            $newId = max($newId, $transaction->getId() + 1);
        }

        // Membuat transaksi baru
        $newTransaction = new Transaction(
            $newId,
            $memberId,
            $bookId,
            $book->getTitle(),
            date('Y-m-d')
        );

        // Mengubah status buku
        $book->setStatus("Dipinjam");

        if (!$this->bookService->update($book)) {
            return false;
        }

        // Menyimpan transaksi
        $transactions[] = $newTransaction;

        if (!$this->save($transactions)) {
            // Mengembalikan status buku jika penyimpanan gagal
            $book->setStatus("Tersedia");
            $this->bookService->update($book);

            return false;
        }

        return true;
    }

    // Mengambil transaksi berdasarkan member
    public function getByMemberId(int $memberId): array
    {
        $result = [];

        foreach ($this->getAll() as $transaction) {
            if ($transaction->getMemberId() === $memberId) {
                $result[] = $transaction;
            }
        }

        return $result;
    }

    // Mengembalikan buku
    public function returnBook(
        int $transactionId,
        int $memberId
    ): bool {

        $transactions = $this->getAll();

        foreach ($transactions as $transaction) {

            if (
                $transaction->getId() === $transactionId &&
                $transaction->getMemberId() === $memberId &&
                $transaction->getStatus() === "Dipinjam"
            ) {

                $book = $this->bookService->findById(
                    $transaction->getBookId()
                );

                if ($book === null) {
                    return false;
                }

                // Mengubah status buku
                $book->setStatus("Tersedia");

                if (!$this->bookService->update($book)) {
                    return false;
                }

                // Mengubah status transaksi
                $transaction->returnBook();

                if (!$this->save($transactions)) {
                    // Mengembalikan status buku jika gagal menyimpan
                    $book->setStatus("Dipinjam");
                    $this->bookService->update($book);

                    return false;
                }

                return true;
            }
        }

        return false;
    }
}
