<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class OpenFoodFactsService
{
    protected string $userAgent = 'GnuffApp - Web - v1.0 - https://github.com/negrognuff/gnuff - scan';
    protected string $baseUrl = 'https://world.openfoodfacts.net/api/v2';

    /**
     * Recupera i dati di un prodotto tramite barcode.
     *
     * La chiamata API richiede sempre e solo i seguenti parametri:
     *   - lc: it (lingua)
     *   - cc: it (paese)
     *   - fields: code,product_name,image_url (solo questi campi)
     *
     * @param string $barcode
     * @return array Array con chiavi:
     *   - status: 1 se trovato, 0 se non trovato
     *   - product: array|null (solo code, product_name, image_url)
     *   - error: string|null
     */
    public function getProductByBarcode(string $barcode): array
    {
        $store = app()->environment('testing') ? 'array' : 'redis';
        $cacheKey = "off_product_{$barcode}";

        if (Cache::store($store)->has($cacheKey)) {
            $cached = Cache::store($store)->get($cacheKey);
            if (is_array($cached)) {
                return $cached;
            }
        }

        $params = [
            'lc' => 'it',
            'cc' => 'it',
            'fields' => 'code,product_name,image_url',
        ];
        $query = http_build_query($params);
        try {
            $response = Http::withHeaders([
                'User-Agent' => $this->userAgent,
                'Accept' => 'application/json',
            ])->get("{$this->baseUrl}/product/{$barcode}?{$query}");
        } catch (\Exception $e) {
            Log::warning('OpenFoodFacts product lookup failed due to connection issue.', [
                'barcode' => $barcode,
                'exception' => $e,
            ]);

            return [
                'error' => 'Errore di connessione a OpenFoodFacts',
                'status' => null,
                'product' => null,
            ];
        }

        if ($response->successful()) {
            $json = $response->json();
            $result = [
                'status' => $json['status'] ?? null,
                'product' => $json['product'] ?? null,
                'error' => null,
            ];

            // Cache per 1 giorno se il prodotto è stato cercato con successo (trovato o non trovato)
            if (isset($json['status'])) {
                Cache::store($store)->put($cacheKey, $result, now()->addDay());
            }

            return $result;
        }

        Log::warning('OpenFoodFacts product lookup returned non-success status.', [
            'barcode' => $barcode,
            'http_status' => $response->status(),
        ]);

        return [
            'status' => null,
            'product' => null,
            'error' => 'Errore nella risposta da OpenFoodFacts',
        ];
    }
    /**
     * Ricerca prodotti tramite parametri (per seeder, ecc)
     * @param array $params
     * @return array|null
     */
    public function searchProducts(array $params): ?array
    {
        $query = http_build_query($params);
        try {
            $response = Http::withHeaders([
                'User-Agent' => $this->userAgent,
                'Accept' => 'application/json',
            ])->get("{$this->baseUrl}/search?{$query}");
        } catch (\Exception $e) {
            Log::warning('OpenFoodFacts search failed due to connection issue.', [
                'params' => $params,
                'exception' => $e,
            ]);

            return null;
        }
        if ($response->successful()) {
            $json = $response->json();
            return $json['products'] ?? null;
        }

        Log::warning('OpenFoodFacts search returned non-success status.', [
            'params' => $params,
            'http_status' => $response->status(),
        ]);

        return null;
    }
}
