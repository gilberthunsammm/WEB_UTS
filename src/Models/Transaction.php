<?php

namespace App\Models;

class Transaction
{
    private int $id;
    private int $memberId;
    private int $bookId;
    private string $bookTitle;
    private string $borrowDate;
    private string $status;

    public function __construct(
        int $id,
        int $memberId,
        int $bookId,
        string $bookTitle,
        string $borrowDate,
        string $status = "Dipinjam"
    ) {
        $this->id = $id;
        $this->memberId = $memberId;
        $this->bookId = $bookId;
        $this->bookTitle = $bookTitle;
        $this->borrowDate = $borrowDate;
        $this->status = $status;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getMemberId(): int
    {
        return $this->memberId;
    }

    public function getBookId(): int
    {
        return $this->bookId;
    }

    public function getBookTitle(): string
    {
        return $this->bookTitle;
    }

    public function getBorrowDate(): string
    {
        return $this->borrowDate;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    // Mengubah status peminjaman
    public function returnBook(): void
    {
        $this->status = "Dikembalikan";
    }

    // Mengubah object menjadi array
    public function toArray(): array
    {
        return [
            "id" => $this->id,
            "memberId" => $this->memberId,
            "bookId" => $this->bookId,
            "bookTitle" => $this->bookTitle,
            "borrowDate" => $this->borrowDate,
            "status" => $this->status
        ];
    }
}
