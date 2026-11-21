<?php

namespace App\Models;

class User
{
    private int $id;
    private string $name;
    private string $email;
    private string $password;

    public function __construct(
        int $id,
        string $name,
        string $email,
        string $password
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
        $this->password = $password;
    }

    // Getter
    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    // Memeriksa password
    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->password);
    }

    // Method yang dapat diubah oleh class turunan
    public function getRole(): string
    {
        return "Pengguna";
    }

    public function getDashboardMessage(): string
    {
        return "Selamat datang di Digital Library.";
    }

    // Mengubah object menjadi array
    public function toArray(): array
    {
        return [
            "id" => $this->id,
            "name" => $this->name,
            "email" => $this->email,
            "password" => $this->password,
            "role" => $this->getRole()
        ];
    }
}
