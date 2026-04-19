<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class OpenFoodFactsService
{
    protected string $userAgent = 'GnuffApp - Web - v1.0 - https://github.com/negrognuff/gnuff - scan';
    protected string $baseUrl = 'https://world.openfoodfacts.org/api/v2';

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
            return [
                'error' => 'Errore di connessione a OpenFoodFacts',
                'status' => null,
                'product' => null,
            ];
        }

        if ($response->successful()) {
            $json = $response->json();
            return [
                'status' => $json['status'] ?? null,
                'product' => $json['product'] ?? null,
                'error' => null,
            ];
        }
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
            return null;
        }
        if ($response->successful()) {
            $json = $response->json();
            return $json['products'] ?? null;
        }
        return null;
    }
}
