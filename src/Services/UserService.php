<?php

namespace App\Services;

use App\Models\User;
use App\Models\Member;
use App\Models\Admin;
use App\Contracts\UserRepositoryInterface;

/**
 * UserService — Implementasi nyata dari UserRepositoryInterface.
 * Menyimpan data pengguna di storage JSON.
 * Polymorphism via Interface: kelas ini memenuhi kontrak UserRepositoryInterface.
 */
class UserService implements UserRepositoryInterface
{
    private string $filePath;

    public function __construct()
    {
        $this->filePath = __DIR__ . '/../../data/users.json';
    }

    // Membaca seluruh data pengguna
    public function getAll(): array
    {
        if (!file_exists($this->filePath)) {
            return [];
        }

        $data = file_get_contents($this->filePath);
        $users = json_decode($data, true);

        return is_array($users) ? $users : [];
    }

    // Mencari pengguna berdasarkan email
    public function findByEmail(string $email): ?User
    {
        foreach ($this->getAll() as $user) {
            if (strtolower($user['email']) === strtolower($email)) {
                $role = $user['role'] ?? 'Member';

                if ($role === 'Admin') {
                    return new Admin(
                        (int) $user['id'],
                        $user['name'],
                        $user['email'],
                        $user['password'],
                        $user['registrationDate'] ?? date('Y-m-d')
                    );
                }

                return new Member(
                    (int) $user['id'],
                    $user['name'],
                    $user['email'],
                    $user['password'],
                    $user['registrationDate'] ?? date('Y-m-d')
                );
            }
        }

        return null;
    }

    // Registrasi member baru
    public function register(
        string $name,
        string $email,
        string $password
    ): bool {
        $users = $this->getAll();

        // Memeriksa email yang sudah terdaftar
        if ($this->findByEmail($email) !== null) {
            return false;
        }

        // Membuat ID otomatis
        $ids = array_column($users, 'id');
        $newId = empty($ids) ? 1 : max($ids) + 1;

        // Membuat password hash
        $hashedPassword = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        // Membuat objek Member baru
        $member = new Member(
            $newId,
            $name,
            $email,
            $hashedPassword,
            date('Y-m-d')
        );

        // Menambahkan data member
        $users[] = $member->toArray();

        return $this->save($users);
    }

    // Proses login
    public function login(
        string $email,
        string $password
    ): ?User {
        $user = $this->findByEmail($email);

        if ($user === null) {
            return null;
        }

        if (!$user->verifyPassword($password)) {
            return null;
        }

        return $user;
    }

    // Menyimpan data ke JSON
    private function save(array $users): bool
    {
        $json = json_encode(
            $users,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
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
}
