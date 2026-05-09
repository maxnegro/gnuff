<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class ProductImageCacheService
{
    public function isLocalStorageUrl(?string $url): bool
    {
        if (!$url) {
            return false;
        }

        if (str_starts_with($url, '/storage/')) {
            return true;
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $path = parse_url($url, PHP_URL_PATH);

        return is_string($path) && str_starts_with($path, '/storage/');
    }

    public function toStoragePath(?string $url): ?string
    {
        if (!$this->isLocalStorageUrl($url)) {
            return null;
        }

        if (str_starts_with((string) $url, '/storage/')) {
            return (string) $url;
        }

        $path = parse_url((string) $url, PHP_URL_PATH);

        return is_string($path) ? $path : null;
    }

    public function cacheRemoteImage(?string $remoteUrl, string $barcode): ?string
    {
        if (!$remoteUrl) {
            return null;
        }

        // Già locale: non fare round-trip esterno.
        if ($this->isLocalStorageUrl($remoteUrl)) {
            return $this->toStoragePath($remoteUrl);
        }

        $scheme = parse_url($remoteUrl, PHP_URL_SCHEME);
        if (!in_array($scheme, ['http', 'https'], true)) {
            return null;
        }

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Accept' => 'image/*,*/*;q=0.8',
                    'User-Agent' => 'GnuffApp Image Cache/1.0',
                ])
                ->get($remoteUrl);
        } catch (\Throwable $e) {
            return null;
        }

        if (!$response->successful()) {
            return null;
        }

        $body = $response->body();
        if ($body === '') {
            return null;
        }

        // Evita di salvare payload non immagine (es. HTML di errore) con estensione jpg/png.
        if (@getimagesizefromstring($body) === false) {
            return null;
        }

        $contentType = strtolower((string) $response->header('Content-Type'));
        if ($this->isClearlyNotImage($contentType)) {
            return null;
        }

        $extension = $this->guessExtension($remoteUrl, $contentType);
        $path = $this->buildStoragePath($barcode, $remoteUrl, $extension);

        Storage::disk('public')->put($path, $body);

        return Storage::disk('public')->url($path);
    }

    private function buildStoragePath(string $barcode, string $remoteUrl, string $extension): string
    {
        $treeHash = sha1($barcode);
        $levelOne = substr($treeHash, 0, 2);
        $levelTwo = substr($treeHash, 2, 2);
        $fileHash = substr(sha1($remoteUrl), 0, 16);

        return "products/{$levelOne}/{$levelTwo}/{$barcode}-{$fileHash}.{$extension}";
    }

    private function isClearlyNotImage(string $contentType): bool
    {
        if ($contentType === '') {
            return false;
        }

        if (str_starts_with($contentType, 'image/')) {
            return false;
        }

        return str_starts_with($contentType, 'text/')
            || str_contains($contentType, 'application/json')
            || str_contains($contentType, 'application/xml')
            || str_contains($contentType, 'text/html');
    }

    private function guessExtension(string $url, string $contentType): string
    {
        if (str_contains($contentType, 'image/jpeg')) {
            return 'jpg';
        }

        if (str_contains($contentType, 'image/png')) {
            return 'png';
        }

        if (str_contains($contentType, 'image/webp')) {
            return 'webp';
        }

        if (str_contains($contentType, 'image/gif')) {
            return 'gif';
        }

        $path = parse_url($url, PHP_URL_PATH) ?: '';
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)
            ? ($extension === 'jpeg' ? 'jpg' : $extension)
            : 'jpg';
    }
}
