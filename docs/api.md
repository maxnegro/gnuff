# API Documentation

Questo documento descrive le API RESTful disponibili nel progetto GNuff, con esempi di utilizzo.

## Base URL

```
/api
```

## Autenticazione

Le API richiedono autenticazione tramite **Laravel Sanctum**. Includere il token nell'header:

```
Authorization: Bearer {sanctum_token}
```

## Formato risposte

Tutte le risposte JSON seguono uno schema standardizzato:

```json
{
  "data": { ... },
  "message": "string"
}
```

In caso di errore:

```json
{
  "error": {
    "code": "ERROR_CODE",
    "message": "Messaggio descrittivo",
    "details": {}
  }
}
```

---

## Endpoints API

### 1. Prodotti

#### GET `/api/products`

Restituisce una lista paginata di prodotti.

**Query Parameters:**

| Parametro | Tipo | Descrizione |
|-----------|------|-------------|
| `list_id` | integer | (Opzionale) Filtra prodotti per lista |

**Esempio richiesta:**

```bash
curl -X GET "https://gnuff.test/api/products?list_id=1" \
  -H "Authorization: Bearer {token}"
```

**Esempio risposta:**

```json
{
  "data": [
    {
      "id": 1,
      "barcode": "8004702812345",
      "name": "Pasta Barilla",
      "image_url": "/storage/products/ab/cd/8004702812345-abc123.jpg",
      "ratings": [
        {
          "id": 5,
          "rating": "gnuf",
          "author_name": "Mario"
        }
      ]
    }
  ],
  "current_page": 1,
  "total": 42
}
```

---

#### GET `/api/product/{barcode}`

Recupera un singolo prodotto per codice a barre. Se non esiste nel database locale, lo cerca su OpenFoodFacts e lo salva.

**Path Parameters:**

| Parametro | Tipo | Descrizione |
|-----------|------|-------------|
| `barcode` | string | Codice a barre EAN-13/UPC |

**Esempio richiesta:**

```bash
curl -X GET "https://gnuff.test/api/product/8004702812345" \
  -H "Authorization: Bearer {token}"
```

**Esempio risposta (prodotto nuovo da OpenFoodFacts):**

```json
{
  "product": {
    "id": 42,
    "barcode": "8004702812345",
    "name": "Pasta Barilla",
    "image_url": "https://world.openfoodfacts.org/images/products/800/470/281/2345/front_it.400.jpg",
    "created_at": "2026-06-13T08:00:00.000000Z"
  },
  "active_list": {
    "id": 1,
    "name": "Default"
  },
  "user_rating": "ok"
}
```

---

#### POST `/api/product`

Crea o aggiorna un prodotto. Se il barcode esiste, aggiorna name e image_url.

**Request Body:**

```json
{
  "barcode": "8004702812345",
  "name": "Pasta Barilla",
  "image_url": "https://example.com/image.jpg"
}
```

**Validazione:**

- `barcode`: obbligatorio, stringa
- `name`: opzionale, stringa, max 255
- `image_url`: opzionale, URL valido oppure percorso `/storage/`

**Esempio richiesta:**

```bash
curl -X POST "https://gnuff.test/api/product" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "barcode": "8004702812345",
    "name": "Pasta Barilla",
    "image_url": "https://world.openfoodfacts.org/images/products/800/470/281/2345/front_it.400.jpg"
  }'
```

**Esempio risposta (creato):**

```json
{
  "product": {
    "id": 42,
    "barcode": "8004702812345",
    "name": "Pasta Barilla",
    "image_url": "/storage/products/ab/cd/8004702812345-abc123.jpg",
    "created_at": "2026-06-13T08:00:00.000000Z"
  }
}
```

---

#### PUT `/api/product/{barcode}`

Aggiorna name e/o image_url di un prodotto esistente.

**Path Parameters:**

| Parametro | Tipo | Descrizione |
|-----------|------|-------------|
| `barcode` | string | Codice a barre EAN-13/UPC |

**Request Body:**

```json
{
  "name": "Pasta Barilla Integrale",
  "image_url": "/storage/products/ef/gh/8004702812345-xyz789.jpg"
}
```

**Esempio richiesta:**

```bash
curl -X PUT "https://gnuff.test/api/product/8004702812345" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Pasta Barilla Integrale",
    "image_url": "/storage/products/ef/gh/8004702812345-xyz789.jpg"
  }'
```

---

#### DELETE `/api/product/{product}`

Elimina un prodotto.

**Path Parameters:**

| Parametro | Tipo | Descrizione |
|-----------|------|-------------|
| `product` | integer | ID del prodotto |

**Esempio richiesta:**

```bash
curl -X DELETE "https://gnuff.test/api/product/42" \
  -H "Authorization: Bearer {token}"
```

**Esempio risposta:**

```json
{
  "message": "Product deleted successfully"
}
```

---

### 2. Rating

#### POST `/api/rate`

Crea o aggiorna una valutazione per un prodotto in una lista.

**Request Body:**

```json
{
  "product_id": 42,
  "product_list_id": 1,
  "rating": "gnuf",
  "author_name": "Mario"
}
```

**Validazione:**

- `product_id`: obbligatorio, integer, esiste in `products`
- `product_list_id`: obbligatorio, integer, esiste in `product_lists`
- `rating`: obbligatorio, enum `gnuf|ok|meh|bleah`
- `author_name`: opzionale, stringa, max 255

**Rating disponibili:**

| Valore | Emoji | Descrizione |
|--------|-------|-------------|
| `gnuf` | 👃 | Ottimo |
| `ok` | 👍 | Accettabile |
| `meh` | 🤷 | Cosi così |
| `bleah` | 🤢 | Pessimo |

**Esempio richiesta:**

```bash
curl -X POST "https://gnuff.test/api/rate" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "product_id": 42,
    "product_list_id": 1,
    "rating": "gnuf",
    "author_name": "Mario"
  }'
```

**Esempio risposta:**

```json
{
  "rating": {
    "id": 5,
    "product_id": 42,
    "product_list_id": 1,
    "rating": "gnuf",
    "author_name": "Mario",
    "created_at": "2026-06-13T08:00:00.000000Z"
  }
}
```

---

#### PUT `/api/rate/{rating}`

Aggiorna una valutazione esistente.

**Esempio richiesta:**

```bash
curl -X PUT "https://gnuff.test/api/rate/5" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "rating": "ok"
  }'
```

---

#### DELETE `/api/rate/{rating}`

Elimina una valutazione.

**Esempio richiesta:**

```bash
curl -X DELETE "https://gnuff.test/api/rate/5" \
  -H "Authorization: Bearer {token}"
```

---

#### GET `/api/user/ratings`

Restituisce le ultime 10 valutazioni dell'utente per la lista attiva.

**Query Parameters:**

| Parametro | Tipo | Descrizione |
|-----------|------|-------------|
| `list_id` | integer | (Opzionale) ID della lista |

**Esempio richiesta:**

```bash
curl -X GET "https://gnuff.test/api/user/ratings" \
  -H "Authorization: Bearer {token}"
```

**Esempio risposta:**

```json
{
  "ratings": [
    {
      "id": 5,
      "rating": "gnuf",
      "product": {
        "id": 42,
        "barcode": "8004702812345",
        "name": "Pasta Barilla",
        "image_url": "/storage/products/ab/cd/8004702812345-abc123.jpg"
      },
      "product_list": {
        "id": 1,
        "name": "Default"
      }
    }
  ]
}
```

---

#### GET `/api/ratings`

Restituisce tutte le valutazioni, paginate.

**Query Parameters:**

| Parametro | Tipo | Descrizione |
|-----------|------|-------------|
| `list_id` | integer | (Opzionale) Filtra per lista |

**Esempio richiesta:**

```bash
curl -X GET "https://gnuff.test/api/ratings?list_id=1&page=1" \
  -H "Authorization: Bearer {token}"
```

---

### 3. Liste Prodotti

#### GET `/api/lists/active-and-all`

Restituisce la lista attiva e tutte le liste dell'utente.

**Esempio richiesta:**

```bash
curl -X GET "https://gnuff.test/api/lists/active-and-all" \
  -H "Authorization: Bearer {token}"
```

**Esempio risposta:**

```json
{
  "active": {
    "id": 1,
    "name": "Default",
    "owner_id": 1
  },
  "all": [
    {
      "id": 1,
      "name": "Default",
      "owner_id": 1,
      "users": []
    },
    {
      "id": 2,
      "name": "Lista Vacanze",
      "owner_id": 1,
      "users": []
    }
  ]
}
```

---

## Rate Limiting

Le API sono protette da rate limiting:

| Middleware | Limite | Scopo |
|------------|--------|-------|
| `throttle:api` | 60 richieste/minuto per utente | Generale |
| `throttle:product_lookup` | 30 richieste/minuto per utente | Lookup prodotto lato Gnuff |

Il lookup prodotto può chiamare OpenFoodFacts. Per rispettare i limiti upstream di OpenFoodFacts, Gnuff applica anche una quota server-wide prima di effettuare chiamate reali all'API esterna. Questa quota è conteggiata solo sui cache miss e usa l'identità del server Gnuff, non l'IP del client.

| Origine | Limite | Chiave |
|---------|--------|--------|
| Gnuff API generale | 60 richieste/minuto | utente o IP client |
| Gnuff product lookup | 30 richieste/minuto | utente o IP client |
| OpenFoodFacts product read | 15 richieste/minuto | server Gnuff |
| OpenFoodFacts search | 10 richieste/minuto | server Gnuff |

Le intestazioni di risposta includono i limiti correnti:

```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 58
```

Quando OpenFoodFacts restituisce `429`, `503` o un timeout, Gnuff evita nuove chiamate reali per un breve intervallo, usa la cache locale quando disponibile e restituisce un errore controllato con `error_code` nei `details`, ad esempio `OFF_RATE_LIMITED` o `OFF_UNAVAILABLE`.

---

## Codici di errore

| Codice | HTTP | Descrizione |
|--------|------|-------------|
| `INVALID_IMAGE_BASE64` | 422 | Base64 non valido o formato non supportato |
| `IMAGE_TOO_LARGE` | 422 | Immagine supera i 5MB |
| `INVALID_URL` | 422 | URL immagine non valido |
| `UNAUTHORIZED` | 401 | Token non valido o mancante |
| `FORBIDDEN` | 403 | Utente non autorizzato sulla risorsa |
| `NOT_FOUND` | 404 | Risorsa non trovata |
| `VALIDATION_ERROR` | 422 | Dati di input non validi |
| `RATE_LIMIT_EXCEEDED` | 429 | Limite di richieste superato |

## Versionamento

Attualmente l'API non prevede versionamento URL. Eventuali modifiche breaking saranno gestite tramite header `API-Version`.
