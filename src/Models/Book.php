<?php

namespace App\Models;

/**
 * Class Book
 * Model entitas buku pada sistem Digital Library.
 * Menerapkan prinsip Encapsulation dengan typed properties private,
 * serta mendukung metadata kaya hasil integrasi Google Books API.
 */
class Book
{
    private int $id;
    private string $title;
    private string $author;
    private string $category;
    private string $status;
    private string $coverImage;
    private string $description;
    private string $isbn;
    private string $googleBooksId;
    private string $publishedYear;
    private int $pageCount;
    private string $publisher;

    public function __construct(
        int $id,
        string $title,
        string $author,
        string $category,
        string $status = "Tersedia",
        string $coverImage = "",
        string $description = "",
        string $isbn = "",
        string $googleBooksId = "",
        string $publishedYear = "",
        int $pageCount = 0,
        string $publisher = ""
    ) {
        $this->id            = $id;
        $this->title         = $title;
        $this->author        = $author;
        $this->category      = $category;
        $this->status        = $status;
        $this->coverImage    = $coverImage;
        $this->description   = $description;
        $this->isbn          = $isbn;
        $this->googleBooksId = $googleBooksId;
        $this->publishedYear = $publishedYear;
        $this->pageCount     = $pageCount;
        $this->publisher     = $publisher;
    }

    // Getters
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

    public function getCoverImage(): string
    {
        return $this->coverImage;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getIsbn(): string
    {
        return $this->isbn;
    }

    public function getGoogleBooksId(): string
    {
        return $this->googleBooksId;
    }

    public function getPublishedYear(): string
    {
        return $this->publishedYear;
    }

    public function getPageCount(): int
    {
        return $this->pageCount;
    }

    public function getPublisher(): string
    {
        return $this->publisher;
    }

    // Setters
    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function setCoverImage(string $coverImage): void
    {
        $this->coverImage = $coverImage;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    /**
     * Serialisasi objek menjadi array untuk disimpan ke storage JSON
     */
    public function toArray(): array
    {
        $data = [
            "id"       => $this->id,
            "title"    => $this->title,
            "author"   => $this->author,
            "category" => $this->category,
            "status"   => $this->status,
        ];

        if ($this->coverImage !== '') {
            $data['coverImage'] = $this->coverImage;
        }
        if ($this->description !== '') {
            $data['description'] = $this->description;
        }
        if ($this->isbn !== '') {
            $data['isbn'] = $this->isbn;
        }
        if ($this->googleBooksId !== '') {
            $data['googleBooksId'] = $this->googleBooksId;
        }
        if ($this->publishedYear !== '') {
            $data['publishedYear'] = $this->publishedYear;
        }
        if ($this->pageCount > 0) {
            $data['pageCount'] = $this->pageCount;
        }
        if ($this->publisher !== '') {
            $data['publisher'] = $this->publisher;
        }

        return $data;
    }
}
