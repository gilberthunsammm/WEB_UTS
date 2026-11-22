<?php

namespace App\Models;

/**
 * Class Admin — Representasi Pengguna dengan Hak Akses Administrator
 * Mewarisi User dan mengoverride perilaku peran serta pesan dashboard (Polymorphism).
 */
class Admin extends User
{
    private string $registrationDate;

    public function __construct(
        int $id,
        string $name,
        string $email,
        string $password,
        string $registrationDate = ''
    ) {
        parent::__construct(
            $id,
            $name,
            $email,
            $password
        );

        $this->registrationDate = $registrationDate ?: date('Y-m-d');
    }

    public function getRegistrationDate(): string
    {
        return $this->registrationDate;
    }

    // Polymorphism: overriding method dari User
    public function getRole(): string
    {
        return "Admin";
    }

    // Polymorphism: pesan khusus Admin di dashboard
    public function getDashboardMessage(): string
    {
        return "Selamat datang, Administrator! Anda memiliki akses penuh ke pengelolaan e-book dan sistem perpustakaan.";
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            "registrationDate" => $this->registrationDate
        ]);
    }
}
