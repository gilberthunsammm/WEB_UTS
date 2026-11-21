<?php

/**
 * Konfigurasi Google Books API v1
 * 
 * Pengaturan API Key dapat diset melalui:
 * 1. Environment Variable: GOOGLE_BOOKS_API_KEY
 * 2. File lokal: config/google-books.local.php (jika ada)
 * 3. Nilai default di bawah ini
 */

$localConfigFile = __DIR__ . '/google-books.local.php';
$localConfig = file_exists($localConfigFile) ? require $localConfigFile : [];

$apiKey = getenv('GOOGLE_BOOKS_API_KEY') 
    ?: ($_ENV['GOOGLE_BOOKS_API_KEY'] ?? ($localConfig['api_key'] ?? ''));

return [
    // API Key Google Books (opsional untuk pencarian dasar publik, disarankan jika kuota tinggi)
    'api_key' => $apiKey,

    // Base URL Google Books API v1
    'base_url' => 'https://www.googleapis.com/books/v1',

    // Timeout cURL dalam detik
    'timeout' => 8,

    // Maksimal hasil pencarian per halaman (20 - 30 disarankan)
    'max_results' => 20,

    // Konfigurasi Caching untuk menghemat kuota & mencegah rate limit 429
    'cache_enabled' => true,
    'cache_ttl' => 3600, // 1 jam (dalam detik)
    'cache_dir' => __DIR__ . '/../data/cache',
];
