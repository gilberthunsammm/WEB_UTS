<?php

namespace App\Models;

class Member extends User
{
    private string $registrationDate;

    public function __construct(
        int $id,
        string $name,
        string $email,
        string $password,
        string $registrationDate
    ) {
        parent::__construct(
            $id,
            $name,
            $email,
            $password
        );

        $this->registrationDate = $registrationDate;
    }

    public function getRegistrationDate(): string
    {
        return $this->registrationDate;
    }

    // Polymorphism: overriding method dari User
    public function getRole(): string
    {
        return "Member";
    }

    // Polymorphism: perilaku khusus Member
    public function getDashboardMessage(): string
    {
        return "Selamat datang, Member Digital Library! Silakan nikmati koleksi e-book kami.";
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            "registrationDate" => $this->registrationDate
        ]);
    }
}
