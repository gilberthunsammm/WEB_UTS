<?php

// Autoload class secara otomatis
spl_autoload_register(function ($class) {

    // Namespace App\ diarahkan ke folder src/
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../src/';

    // Periksa namespace class
    $length = strlen($prefix);

    if (strncmp($prefix, $class, $length) !== 0) {
        return;
    }

    // Mengambil nama class setelah namespace App\
    $relativeClass = substr($class, $length);

    // Mengubah namespace menjadi lokasi file
    $file = $baseDir . str_replace(
        '\\',
        '/',
        $relativeClass
    ) . '.php';

    // Memanggil file jika tersedia
    if (file_exists($file)) {
        require_once $file;
    }
});
