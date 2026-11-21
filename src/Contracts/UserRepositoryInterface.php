<?php

namespace App\Contracts;

use App\Models\Member;

/**
 * Interface UserRepositoryInterface
 * Mendefinisikan kontrak operasi manajemen pengguna.
 */
interface UserRepositoryInterface
{
    /**
     * Mengambil seluruh data pengguna.
     */
    public function getAll(): array;

    /**
     * Mencari pengguna berdasarkan alamat email.
     */
    public function findByEmail(string $email): ?Member;

    /**
     * Mendaftarkan member baru.
     */
    public function register(string $name, string $email, string $password): bool;

    /**
     * Memproses login dan mengembalikan objek Member jika berhasil.
     */
    public function login(string $email, string $password): ?Member;
}
