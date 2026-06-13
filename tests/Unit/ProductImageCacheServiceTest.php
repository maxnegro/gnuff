<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\ProductImageCacheService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductImageCacheServiceTest extends TestCase
{
    private function validImageBytes(): string
    {
        return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO7Z4WQAAAAASUVORK5CYII=');
    }

    public function test_absolute_local_storage_url_is_normalized_to_relative_storage_path(): void
    {
        $service = new ProductImageCacheService;

        $localUrl = $service->cacheRemoteImage(
            'http://localhost:8180/storage/products/40/3f/8001720500049-0d55d5504e39c849.jpg',
            '8001720500049'
        );

        $this->assertSame('/storage/products/40/3f/8001720500049-0d55d5504e39c849.jpg', $localUrl);
    }

    public function test_caches_remote_image_and_returns_local_url(): void
    {
        Storage::fake('public');

        Http::fake([
            'https://example.com/*' => Http::response($this->validImageBytes(), 200, [
                'Content-Type' => 'image/png',
            ]),
        ]);

        $service = new ProductImageCacheService;
        $localUrl = $service->cacheRemoteImage('https://example.com/image.png', '1234567890123');

        $this->assertNotNull($localUrl);
        $this->assertMatchesRegularExpression('#^/storage/products/[a-f0-9]{2}/[a-f0-9]{2}/1234567890123-#', $localUrl);
        $this->assertStringEndsWith('.png', $localUrl);

        $storedPath = ltrim(str_replace('/storage/', '', (string) $localUrl), '/');
        Storage::disk('public')->assertExists($storedPath);
    }

    public function test_uses_existing_local_image_without_downloading_again(): void
    {
        Storage::fake('public');

        $barcode = '1234567890123';
        $treeHash = sha1($barcode);
        $path = sprintf(
            'products/%s/%s/%s-%s.png',
            substr($treeHash, 0, 2),
            substr($treeHash, 2, 2),
            $barcode,
            substr(sha1('https://example.com/existing.png'), 0, 16)
        );
        Storage::disk('public')->put($path, $this->validImageBytes());

        $service = new ProductImageCacheService;
        $localUrl = $service->cacheRemoteImage('https://example.com/existing.png', $barcode);

        $this->assertSame(Storage::disk('public')->url($path), $localUrl);
        Http::assertNothingSent();
    }

    public function test_reuses_content_type_cached_image_when_url_extension_differs(): void
    {
        Storage::fake('public');

        Http::fake([
            'https://example.com/*' => Http::response($this->validImageBytes(), 200, [
                'Content-Type' => 'image/png',
            ]),
        ]);

        $service = new ProductImageCacheService;
        $firstUrl = $service->cacheRemoteImage('https://example.com/image.jpg', '1234567890123');
        $secondUrl = $service->cacheRemoteImage('https://example.com/image.jpg', '1234567890123');

        $this->assertSame($firstUrl, $secondUrl);
        $this->assertStringEndsWith('.png', (string) $firstUrl);
        Http::assertSentCount(1);
    }

    public function test_returns_null_for_invalid_url_scheme(): void
    {
        $service = new ProductImageCacheService;

        $localUrl = $service->cacheRemoteImage('file:///tmp/not-allowed.png', '1234567890123');

        $this->assertNull($localUrl);
    }

    public function test_returns_null_on_http_error(): void
    {
        Storage::fake('public');

        Http::fake([
            'https://example.com/*' => Http::response('not found', 404),
        ]);

        $service = new ProductImageCacheService;
        $localUrl = $service->cacheRemoteImage('https://example.com/missing.jpg', '1234567890123');

        $this->assertNull($localUrl);
        Storage::disk('public')->assertDirectoryEmpty('products');
    }

    public function test_falls_back_to_jpg_extension_when_unknown_content_type(): void
    {
        Storage::fake('public');

        Http::fake([
            'https://example.com/*' => Http::response($this->validImageBytes(), 200, [
                'Content-Type' => '',
            ]),
        ]);

        $service = new ProductImageCacheService;
        $localUrl = $service->cacheRemoteImage('https://example.com/no-extension', '1234567890123');

        $this->assertNotNull($localUrl);
        $this->assertStringEndsWith('.jpg', $localUrl);

        $storedPath = ltrim(str_replace('/storage/', '', (string) $localUrl), '/');
        Storage::disk('public')->assertExists($storedPath);
    }

    public function test_returns_null_for_clearly_non_image_content_type(): void
    {
        Storage::fake('public');

        Http::fake([
            'https://example.com/*' => Http::response('{"status":"ok"}', 200, [
                'Content-Type' => 'application/json',
            ]),
        ]);

        $service = new ProductImageCacheService;
        $localUrl = $service->cacheRemoteImage('https://example.com/not-image', '1234567890123');

        $this->assertNull($localUrl);
        Storage::disk('public')->assertDirectoryEmpty('products');
    }

    public function test_returns_null_for_invalid_image_payload_even_with_image_content_type(): void
    {
        Storage::fake('public');

        Http::fake([
            'https://example.com/*' => Http::response('<html>not an image</html>', 200, [
                'Content-Type' => 'image/jpeg',
            ]),
        ]);

        $service = new ProductImageCacheService;
        $localUrl = $service->cacheRemoteImage('https://example.com/broken.jpg', '1234567890123');

        $this->assertNull($localUrl);
        Storage::disk('public')->assertDirectoryEmpty('products');
    }
}
