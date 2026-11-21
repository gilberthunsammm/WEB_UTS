<?php

namespace App\Services;

/**
 * Class BookMapper
 * Bertanggung jawab mentransformasikan response JSON mentah dari Google Books API
 * menjadi struktur data ternormalisasi dan aman untuk digunakan di seluruh aplikasi.
 */
class BookMapper
{
    /**
     * Memetakan satu item volume dari Google Books API
     */
    public static function mapVolume(array $item): array
    {
        $id = (string) ($item['id'] ?? '');
        $vol = is_array($item['volumeInfo'] ?? null) ? $item['volumeInfo'] : [];

        // Judul & Subtitle
        $title = trim($vol['title'] ?? 'Tanpa Judul');
        $subtitle = trim($vol['subtitle'] ?? '');

        // Penulis (Authors)
        $authors = [];
        if (!empty($vol['authors']) && is_array($vol['authors'])) {
            foreach ($vol['authors'] as $author) {
                $trimmed = trim((string) $author);
                if ($trimmed !== '') {
                    $authors[] = $trimmed;
                }
            }
        }
        $authorString = !empty($authors) ? implode(', ', $authors) : 'Penulis Tidak Diketahui';

        // Penerbit & Tahun Terbit
        $publisher = trim($vol['publisher'] ?? 'Penerbit Tidak Diketahui');
        $publishedDate = trim($vol['publishedDate'] ?? '');
        $publishedYear = '';
        if ($publishedDate !== '') {
            if (preg_match('/^\d{4}/', $publishedDate, $matches)) {
                $publishedYear = $matches[0];
            }
        }

        // ISBN (Industry Identifiers)
        $isbn10 = '';
        $isbn13 = '';
        if (!empty($vol['industryIdentifiers']) && is_array($vol['industryIdentifiers'])) {
            foreach ($vol['industryIdentifiers'] as $identifier) {
                $type = $identifier['type'] ?? '';
                $val  = trim($identifier['identifier'] ?? '');
                if ($type === 'ISBN_13') {
                    $isbn13 = $val;
                } elseif ($type === 'ISBN_10') {
                    $isbn10 = $val;
                }
            }
        }
        $primaryIsbn = $isbn13 ?: ($isbn10 ?: '');

        // Kategori
        $categories = [];
        if (!empty($vol['categories']) && is_array($vol['categories'])) {
            foreach ($vol['categories'] as $cat) {
                $trimmed = trim((string) $cat);
                if ($trimmed !== '') {
                    $categories[] = $trimmed;
                }
            }
        }
        $primaryCategory = !empty($categories) ? $categories[0] : 'Umum';

        // Rating & Ratings Count
        $rating = isset($vol['averageRating']) ? (float) $vol['averageRating'] : null;
        $ratingsCount = isset($vol['ratingsCount']) ? (int) $vol['ratingsCount'] : 0;

        // Halaman & Bahasa
        $pageCount = isset($vol['pageCount']) ? (int) $vol['pageCount'] : 0;
        $language = strtoupper(trim($vol['language'] ?? 'ID'));

        // Cover Image Links (Prioritas: extraLarge > large > medium > small > thumbnail > smallThumbnail)
        $coverUrl = self::extractBestCoverUrl($vol['imageLinks'] ?? []);

        // Deskripsi (Sanitasi HTML untuk keamanan XSS)
        $rawDescription = (string) ($vol['description'] ?? '');
        $sanitizedDescription = self::sanitizeDescription($rawDescription);

        // Link Eksternal
        $previewLink = self::ensureHttps(trim($vol['previewLink'] ?? ''));
        $infoLink = self::ensureHttps(trim($vol['infoLink'] ?? ''));

        return [
            'id' => $id,
            'googleBooksId' => $id,
            'title' => $title,
            'subtitle' => $subtitle,
            'author' => $authorString,
            'authors' => $authors,
            'publisher' => $publisher,
            'publishedDate' => $publishedDate,
            'publishedYear' => $publishedYear,
            'isbn' => $primaryIsbn,
            'isbn10' => $isbn10,
            'isbn13' => $isbn13,
            'category' => $primaryCategory,
            'categories' => $categories,
            'pageCount' => $pageCount,
            'language' => $language,
            'rating' => $rating,
            'ratingsCount' => $ratingsCount,
            'coverImage' => $coverUrl,
            'description' => $sanitizedDescription,
            'previewLink' => $previewLink,
            'infoLink' => $infoLink,
        ];
    }

    /**
     * Memetakan kumpulan item dari response Google Books API
     */
    public static function mapVolumeList(array $items): array
    {
        $result = [];
        foreach ($items as $item) {
            if (is_array($item)) {
                $mapped = self::mapVolume($item);
                if ($mapped['title'] !== '' && $mapped['id'] !== '') {
                    $result[] = $mapped;
                }
            }
        }
        return $result;
    }

    /**
     * Memilih cover terbaik dengan resolusi tertinggi dan memastikan URL memakai HTTPS
     */
    private static function extractBestCoverUrl($imageLinks): string
    {
        if (!is_array($imageLinks) || empty($imageLinks)) {
            return '';
        }

        $priority = [
            'extraLarge',
            'large',
            'medium',
            'small',
            'thumbnail',
            'smallThumbnail'
        ];

        foreach ($priority as $sizeKey) {
            if (!empty($imageLinks[$sizeKey]) && is_string($imageLinks[$sizeKey])) {
                $url = trim($imageLinks[$sizeKey]);
                if ($url !== '') {
                    return self::ensureHttps($url);
                }
            }
        }

        return '';
    }

    /**
     * Memastikan URL menggunakan protokol HTTPS (mencegah mixed-content)
     */
    private static function ensureHttps(string $url): string
    {
        if (str_starts_with($url, 'http://')) {
            return 'https://' . substr($url, 7);
        }
        return $url;
    }

    /**
     * Sanitasi HTML deskripsi agar aman dari serangan XSS namun tetap menjaga keterbacaan paragraf
     */
    private static function sanitizeDescription(string $html): string
    {
        if ($html === '') {
            return 'Tidak ada sinopsis atau deskripsi tersedia untuk buku ini.';
        }

        // Hapus script, iframe, style, dan attribute berbahaya
        $clean = strip_tags($html, '<p><br><b><i><strong><em><ul><ol><li>');
        $clean = preg_replace('/<([a-z]+)[^>]*>/i', '<$1>', $clean);
        return trim($clean);
    }
}
