<?php

namespace App\Models;

class Book
{
    // Encapsulation: menggunakan private
    private int $id;
    private string $title;
    private string $author;
    private string $category;
    private string $status;

    // Constructor
    public function __construct(
        int $id,
        string $title,
        string $author,
        string $category,
        string $status = "Tersedia"
    ) {
        $this->id = $id;
        $this->title = $title;
        $this->author = $author;
        $this->category = $category;
        $this->status = $status;
    }

    // Getter
    public function getId(): int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getAuthor(): string
    {
        return $this->author;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    // Mengubah status buku
    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    // Mengubah object menjadi array
    public function toArray(): array
    {
        return [
            "id" => $this->id,
            "title" => $this->title,
            "author" => $this->author,
            "category" => $this->category,
            "status" => $this->status
        ];
    }
}
