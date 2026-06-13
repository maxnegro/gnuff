<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class OpenFoodFactsService
{
    protected string $baseUrl = 'https://world.openfoodfacts.net/api/v2';

    protected string $userAgent = 'Gnuff/1.0 (noreply@example.com)';

    /**
     * Recupera i dati di un prodotto tramite barcode.
     *
     * La chiamata API richiede sempre e solo i seguenti parametri:
     *   - lc: it (lingua)
     *   - cc: it (paese)
     *   - fields: code,product_name,image_url (solo questi campi)
     *
     * @return array Array con chiavi:
     *               - status: 1 se trovato, 0 se non trovato
     *               - product: array|null (solo code, product_name, image_url)
     *               - error: string|null
     *               - error_code: string|null
     *               - retry_after: int|null
     */
    public function getProductByBarcode(string $barcode): array
    {
        $barcode = $this->normalizeBarcode($barcode);

        if ($barcode === '') {
            return $this->errorResult(
                'Codice a barre non valido.',
                'OFF_INVALID_BARCODE'
            );
        }

        $cacheKey = $this->productCacheKey($barcode);
        $cached = $this->getCachedResult($cacheKey);

        if ($cached !== null) {
            return $cached;
        }

        $circuitOpenFor = $this->openCircuitSeconds('product');

        if ($circuitOpenFor !== null) {
            return $this->errorResult(
                'OpenFoodFacts temporaneamente non disponibile. Riprova più tardi.',
                'OFF_CIRCUIT_OPEN',
                $circuitOpenFor
            );
        }

        $budget = $this->consumeUpstreamBudget('product');

        if (! $budget['allowed']) {
            $retryAfter = $budget['retry_after'] ?? $this->circuitBreakerSeconds();
            $this->openCircuit('product', $retryAfter);

            return $this->errorResult(
                'Limite OpenFoodFacts raggiunto. Riprova più tardi.',
                'OFF_RATE_LIMITED',
                $retryAfter
            );
        }

        $params = [
            'lc' => 'it',
            'cc' => 'it',
            'fields' => 'code,product_name,image_url',
        ];
        $query = http_build_query($params);

        try {
            $response = $this->http()->get("{$this->baseUrl()}/product/{$barcode}?{$query}");
        } catch (\Exception $e) {
            $retryAfter = $this->circuitBreakerSeconds();
            $this->openCircuit('product', $retryAfter);
            $this->cacheProductError($barcode, $retryAfter, 'Errore di connessione a OpenFoodFacts', 'OFF_CONNECTION_ERROR');

            Log::warning('OpenFoodFacts product lookup failed due to connection issue.', [
                'barcode' => $barcode,
                'exception' => $e,
            ]);

            return $this->errorResult(
                'Errore di connessione a OpenFoodFacts',
                'OFF_CONNECTION_ERROR',
                $retryAfter
            );
        }

        if ($response->status() === 429) {
            $retryAfter = $this->retryAfterFromResponse($response, $this->circuitBreakerSeconds());
            $this->openCircuit('product', $retryAfter);
            $this->cacheProductError($barcode, $retryAfter, 'Limite OpenFoodFacts raggiunto. Riprova più tardi.', 'OFF_RATE_LIMITED');

            Log::warning('OpenFoodFacts product lookup rate limit exceeded.', [
                'barcode' => $barcode,
                'http_status' => $response->status(),
                'retry_after' => $retryAfter,
            ]);

            return $this->errorResult(
                'Limite OpenFoodFacts raggiunto. Riprova più tardi.',
                'OFF_RATE_LIMITED',
                $retryAfter
            );
        }

        if ($response->status() === 503 || $response->status() >= 500) {
            $retryAfter = $this->retryAfterFromResponse($response, $this->circuitBreakerSeconds());
            $this->openCircuit('product', $retryAfter);
            $this->cacheProductError($barcode, $retryAfter, 'OpenFoodFacts temporaneamente non disponibile. Riprova più tardi.', 'OFF_UNAVAILABLE');

            Log::warning('OpenFoodFacts product lookup returned temporary upstream error.', [
                'barcode' => $barcode,
                'http_status' => $response->status(),
                'retry_after' => $retryAfter,
            ]);

            return $this->errorResult(
                'OpenFoodFacts temporaneamente non disponibile. Riprova più tardi.',
                'OFF_UNAVAILABLE',
                $retryAfter
            );
        }

        if ($response->successful()) {
            $json = $response->json();

            if (! is_array($json)) {
                $retryAfter = $this->productErrorTtl();
                $this->cacheProductError($barcode, $retryAfter, 'Errore nella risposta da OpenFoodFacts', 'OFF_INVALID_RESPONSE');

                Log::warning('OpenFoodFacts product lookup returned invalid JSON.', [
                    'barcode' => $barcode,
                    'http_status' => $response->status(),
                ]);

                return $this->errorResult(
                    'Errore nella risposta da OpenFoodFacts',
                    'OFF_INVALID_RESPONSE',
                    $retryAfter
                );
            }

            $status = isset($json['status']) ? (int) $json['status'] : null;
            $product = isset($json['product']) && is_array($json['product']) ? $json['product'] : null;
            $result = [
                'status' => $status,
                'product' => $product,
                'error' => null,
                'error_code' => null,
                'retry_after' => null,
            ];

            if ($status === 1 || $status === 0) {
                $this->cacheStore()->put($cacheKey, $result, now()->addSeconds($this->productStatusTtl($status)));
            } else {
                $retryAfter = $this->productErrorTtl();
                $result = $this->errorResult('Errore nella risposta da OpenFoodFacts', 'OFF_INVALID_RESPONSE', $retryAfter);
                $this->cacheStore()->put($cacheKey, $result, now()->addSeconds($retryAfter));
            }

            return $result;
        }

        $retryAfter = $this->productErrorTtl();
        $this->cacheProductError($barcode, $retryAfter, 'Errore nella risposta da OpenFoodFacts', 'OFF_INVALID_RESPONSE');

        Log::warning('OpenFoodFacts product lookup returned non-success status.', [
            'barcode' => $barcode,
            'http_status' => $response->status(),
        ]);

        return $this->errorResult(
            'Errore nella risposta da OpenFoodFacts',
            'OFF_INVALID_RESPONSE',
            $retryAfter
        );
    }

    /**
     * Ricerca prodotti tramite parametri (per seeder, ecc)
     */
    public function searchProducts(array $params): ?array
    {
        $cacheKey = 'off_search_'.hash('sha256', http_build_query($params));
        $cached = $this->getCachedResult($cacheKey);

        if ($cached !== null && isset($cached['products']) && is_array($cached['products'])) {
            return $cached['products'];
        }

        $circuitOpenFor = $this->openCircuitSeconds('search');

        if ($circuitOpenFor !== null) {
            Log::warning('OpenFoodFacts search skipped because circuit is open.', [
                'retry_after' => $circuitOpenFor,
            ]);

            return null;
        }

        $budget = $this->consumeUpstreamBudget('search');

        if (! $budget['allowed']) {
            $retryAfter = $budget['retry_after'] ?? $this->circuitBreakerSeconds();
            $this->openCircuit('search', $retryAfter);
            Log::warning('OpenFoodFacts search rate limit exceeded.', [
                'retry_after' => $retryAfter,
            ]);

            return null;
        }

        $query = http_build_query($params);

        try {
            $response = $this->http()->get("{$this->baseUrl()}/search?{$query}");
        } catch (\Exception $e) {
            $retryAfter = $this->circuitBreakerSeconds();
            $this->openCircuit('search', $retryAfter);
            Log::warning('OpenFoodFacts search failed due to connection issue.', [
                'params' => $params,
                'exception' => $e,
            ]);

            return null;
        }

        if ($response->status() === 429 || $response->status() === 503 || $response->status() >= 500) {
            $retryAfter = $this->retryAfterFromResponse($response, $this->circuitBreakerSeconds());
            $this->openCircuit('search', $retryAfter);
            Log::warning('OpenFoodFacts search returned temporary upstream error.', [
                'params' => $params,
                'http_status' => $response->status(),
                'retry_after' => $retryAfter,
            ]);

            return null;
        }

        if ($response->successful()) {
            $json = $response->json();
            $products = is_array($json) && isset($json['products']) && is_array($json['products'])
                ? $json['products']
                : [];

            $this->cacheStore()->put($cacheKey, ['products' => $products], now()->addSeconds($this->searchTtl()));

            return $products;
        }

        Log::warning('OpenFoodFacts search returned non-success status.', [
            'params' => $params,
            'http_status' => $response->status(),
        ]);

        return null;
    }

    private function http()
    {
        return Http::timeout($this->requestTimeoutSeconds())
            ->retry(
                $this->retries(),
                $this->retryDelayMilliseconds(),
                null,
                false
            )
            ->withHeaders([
                'User-Agent' => $this->userAgent(),
                'Accept' => 'application/json',
            ]);
    }

    private function normalizeBarcode(string $barcode): string
    {
        $normalized = preg_replace('/\D+/', '', trim($barcode));

        return is_string($normalized) ? $normalized : '';
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('openfoodfacts.base_url', $this->baseUrl), '/');
    }

    private function userAgent(): string
    {
        return (string) config('openfoodfacts.user_agent', $this->userAgent);
    }

    private function requestTimeoutSeconds(): int
    {
        return max(1, (int) config('openfoodfacts.request_timeout_seconds', 5));
    }

    private function retries(): int
    {
        return max(0, (int) config('openfoodfacts.retries', 1));
    }

    private function retryDelayMilliseconds(): int
    {
        return max(0, (int) config('openfoodfacts.retry_delay_milliseconds', 500));
    }

    private function productLookupLimitPerMinute(): int
    {
        return max(1, (int) config('openfoodfacts.product_lookup_limit_per_minute', 15));
    }

    private function searchLimitPerMinute(): int
    {
        return max(1, (int) config('openfoodfacts.search_limit_per_minute', 10));
    }

    private function serverId(): string
    {
        $serverId = trim((string) config('openfoodfacts.server_id', 'default'));

        return $serverId === '' ? 'default' : $serverId;
    }

    private function circuitBreakerSeconds(): int
    {
        return max(1, (int) config('openfoodfacts.circuit_breaker_seconds', 300));
    }

    private function productFoundTtl(): int
    {
        return max(1, (int) config('openfoodfacts.cache.product_found_ttl', 86400));
    }

    private function productNotFoundTtl(): int
    {
        return max(1, (int) config('openfoodfacts.cache.product_not_found_ttl', 86400));
    }

    private function productErrorTtl(): int
    {
        return max(1, (int) config('openfoodfacts.cache.product_error_ttl', 600));
    }

    private function searchTtl(): int
    {
        return max(1, (int) config('openfoodfacts.cache.search_ttl', 3600));
    }

    private function productStatusTtl(int $status): int
    {
        return $status === 0 ? $this->productNotFoundTtl() : $this->productFoundTtl();
    }

    private function cacheStore()
    {
        return Cache::store(app()->environment('testing') ? 'array' : 'redis');
    }

    private function getCachedResult(string $cacheKey): ?array
    {
        $cached = $this->cacheStore()->get($cacheKey);

        return is_array($cached) ? $cached : null;
    }

    private function productCacheKey(string $barcode): string
    {
        return "off_product_{$barcode}";
    }

    private function cacheProductError(string $barcode, int $retryAfter, string $message, string $code): void
    {
        $this->cacheStore()->put(
            $this->productCacheKey($barcode),
            $this->errorResult($message, $code, $retryAfter),
            now()->addSeconds($retryAfter)
        );
    }

    private function consumeUpstreamBudget(string $type): array
    {
        $limit = $type === 'search' ? $this->searchLimitPerMinute() : $this->productLookupLimitPerMinute();
        $key = "openfoodfacts_{$type}_{$this->serverId()}";
        $decay = 60;

        if (RateLimiter::tooManyAttempts($key, $limit)) {
            return [
                'allowed' => false,
                'retry_after' => RateLimiter::availableIn($key),
            ];
        }

        RateLimiter::hit($key, $decay);

        return [
            'allowed' => true,
            'retry_after' => 0,
        ];
    }

    private function circuitKey(string $type): string
    {
        return "openfoodfacts_circuit_{$type}_{$this->serverId()}";
    }

    private function openCircuit(string $type, int $seconds): void
    {
        $seconds = max(1, $seconds);
        $this->cacheStore()->put($this->circuitKey($type), now()->addSeconds($seconds)->timestamp, $seconds);
    }

    private function openCircuitSeconds(string $type): ?int
    {
        $expiresAt = $this->cacheStore()->get($this->circuitKey($type));

        if (! is_numeric($expiresAt)) {
            return null;
        }

        $secondsLeft = (int) $expiresAt - now()->timestamp;

        return $secondsLeft > 0 ? $secondsLeft : null;
    }

    private function retryAfterFromResponse($response, int $fallback): int
    {
        $retryAfter = $response->header('Retry-After');

        if (is_numeric($retryAfter)) {
            return max(1, (int) $retryAfter);
        }

        return $fallback;
    }

    private function errorResult(string $message, string $code, ?int $retryAfter = null): array
    {
        return [
            'status' => null,
            'product' => null,
            'error' => $message,
            'error_code' => $code,
            'retry_after' => $retryAfter,
        ];
    }
}
