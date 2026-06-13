# Guida per Sviluppatori

Questa guida aiuta gli sviluppatori a estendere il sistema GNuff, uno stack Laravel 13 + Inertia.js v2 + Vue 3 per la gestione di liste di prodotti con valutazioni.

## Indice

1. [Architettura del progetto](#architettura-del-progetto)
2. [Stack tecnologico](#stack-tecnologico)
3. [Struttura del codice](#struttura-del-codice)
4. [Pattern applicativi](#pattern-applicativi)
5. [Aggiungere una nuova funzionalità](#aggiungere-una-nuova-funzionalità)
6. [Estendere i modelli](#estendere-i-modelli)
7. [Aggiungere API endpoints](#aggiungere-api-endpoints)
8. [Frontend: componenti e pagine](#frontend-componenti-e-pagine)
9. [Testing](#testing)
10. [Deployment](#deployment)

---

## Architettura del progetto

GNuff è un'applicazione web single-page gestita da Inertia.js. Il backend Laravel espone sia endpoint web (per Inertia) che API REST (per client esterni). I dati fluiscono come segue:

```
Browser → Inertia.js (Vue 3) → Laravel Controller → Eloquent Model → Database
Browser → REST API (Sanctum)   → Laravel Controller → Eloquent Model → Database
```

La funzionalità centrale è la gestione di **liste di prodotti** con **valutazioni**. Un utente possiede liste di prodotti, ogni prodotto in una lista riceve una valutazione (`gnuf|ok|meh|bleah`).

---

## Stack tecnologico

| Livello | Tecnologia | Versione |
|---------|-----------|----------|
| Backend | Laravel | 13.x |
| Frontend | Vue 3 + Inertia.js | v2 |
| Stili | Tailwind CSS | v3 |
| Autenticazione | Laravel Breeze + Sanctum | v4 |
| Database | MySQL/PostgreSQL/SQLite | — |
| Cache | Redis (produzione) / Array (test) | — |
| Testing | PHPUnit | v12 |
| Frontend testing | Vitest | — |
| Formatter | Laravel Pint | v1 |

---

## Struttura del codice

```
app/
  Enums/
    RatingEnum.php              # Enumerazione valutazioni
  Http/
    Controllers/
      Controller.php            # Base controller
      DashboardController.php   # Dashboard homepage
      ProductController.php     # CRUD prodotti + upload immagine
      ProductListController.php # CRUD liste + condivisione
      RatingController.php      # CRUD valutazioni
      ProfileController.php     # Gestione profilo utente
      Auth/                     # Controllers autenticazione Breeze
    Middleware/
      HandleInertiaRequests.php # Props condivisi Inertia (auth, liste)
    Requests/
      ProfileUpdateRequest.php
      UpdateProductImageRequest.php
      Auth/LoginRequest.php
  Models/
    User.php                    # Utente con validation a modello
    Product.php                 # Prodotto (barcode univoco)
    ProductList.php             # Lista prodotti
    Rating.php                  # Valutazione
  Policies/
    ProductListPolicy.php       # Autorizzazioni liste
  Providers/
    AppServiceProvider.php      # Rate limiter, Vite config
    RouteServiceProvider.php    # Mapping route API+web
    AuthServiceProvider.php     # Policy mapping
  Services/
    OpenFoodFactsService.php    # Integrazione API esterna
    ProductImageCacheService.php # Cache immagini remote

database/
  factories/                    # (vuoto - non usati)
  migrations/
    2014_10_12_000000_create_users_table.php
    2025_04_19_100000_create_products_table.php
    2025_04_19_100001_create_product_lists_table.php
    2025_04_19_100002_create_product_list_user_table.php
    2025_04_19_100003_create_product_list_product_table.php
    2025_04_19_100004_safe_ratings_to_lists.php
    (migrazioni Breeze...)

resources/js/
  Pages/                        # Pagine Inertia
    Dashboard.vue
    Scanner.vue
    Product/
      List.vue
    ProductList/
      Index.vue
    Profile/
      Edit.vue
    Auth/                       # Pagine autenticazione Breeze
  Components/                   # Componenti riutilizzabili
    ProductRatingModal.vue      # Modale valutazione prodotto
    RatingSelector.vue          # Selettore valutazione
    ProductPreviewCard.vue
    ProductScanner.vue
    BarcodeScanner.vue
    ImageCropModal.vue
  composables/
    useImageCropper.ts          # Logica crop immagini
  utils/
    imageFileValidation.ts
    imageConverter.ts

routes/
  web.php                       # Route Inertia + web
  api.php                       # Route API Sanctum
  auth.php                      # Route autenticazione Breeze
```

---

## Pattern applicativi

### 1. Validazione a livello di modello

I modelli `User`, `Product` e `Rating` implementano validazione direttamente nel metodo `save()`:

```php
// Esempio da Product.php
public static function rules(): array
{
    return [
        'barcode' => ['required', 'string'],
        'name'    => ['nullable', 'string', 'max:255'],
        'image_url' => ['nullable', 'url'],
    ];
}

public function save(array $options = [])
{
    $this->validate();
    parent::save($options);
}
```

Quando crei un nuovo modello, segui questo pattern per mantenere la coerenza.

### 2. Lista attiva

Il concetto di "lista attiva" è centrale. Viene risolta in questo ordine:

1. Sessione `active_list_id`
2. Campo `selected_list_id` sull'utente
3. Lista con nome `"Default"`
4. Prima lista disponibile

La risoluzione avviene in `HandleInertiaRequests.php` (props condivisi) e in `ProductListController::resolveActiveList()`.

### 3. Storage immagini

Le immagini prodotto sono salvate su disco `public` con path bilanciato:

```
storage/app/public/products/{sha1(barcode)[0-2]}/{sha1(barcode)[2-4]}/{barcode}-{sha1(url)[0:16]}.{ext}
```

Il prefisso `/storage/` rende le immagini accessibili via URL pubblico.

### 4. Enumerazioni

Usa PHP 8.1+ `enum` per valori vincolati. Esempio: `RatingEnum` con valori `gnuf|ok|meh|bleah`.

---

## Aggiungere una nuova funzionalità

### Passo 1: Creare/aggiornare il modello

```bash
vendor/bin/sail artisan make:model NomeModello
```

Implementa `rules()`, `validate()`, e relazioni come da pattern esistente.

### Passo 2: Creare migrazione

```bash
vendor/bin/sail artisan make:migration create_nome_tabella_table
```

### Passo 3: Creare Form Request

```bash
vendor/bin/sail artisan make:request NomeRequest
```

Definisci regole di validazione e metodo `authorize()`.

### Passo 4: Creare Controller

```bash
vendor/bin/sail artisan make:controller NomeController
```

Restituisci sempre risposte Inertia (`Inertia::render()`) per le pagine web oppure JSON per le API.

### Passo 5: Registrare rotte

Aggiungi rotte in `routes/web.php` (Inertia) o `routes/api.php` (Sanctum). Applica middleware di throttling dove appropriato.

### Passo 6: Aggiungere pagina Vue (se serve UI)

Crea la pagina in `resources/js/Pages/` e un layout se serve.

### Passo 7: Scrivere test

Vedi sezione [Testing](#testing).

---

## Estendere i modelli

### Esempio: aggiungere un campo a `Product`

1. Crea migrazione:

```php
Schema::table('products', function (Blueprint $table) {
    $table->string('brand')->nullable()->after('name');
    $table->integer('calories')->nullable()->after('brand');
});
```

2. Aggiorna `Product.php`:

```php
protected $fillable = ['barcode', 'name', 'image_url', 'brand', 'calories'];

public static function rules(): array
{
    return [
        'barcode' => ['required', 'string'],
        'name'    => ['nullable', 'string', 'max:255'],
        'image_url' => ['nullable', 'url'],
        'brand'   => ['nullable', 'string', 'max:255'],
        'calories'=> ['nullable', 'integer', 'min:0'],
    ];
}
```

### Esempio: aggiunta relazione a `Rating`

```php
// In Rating.php
public function productList(): BelongsTo
{
    return $this->belongsTo(ProductList::class);
}
```

---

## Aggiungere API endpoints

Le rotte API sono in `routes/api.php` e usano middleware `auth:sanctum`.

### Esempio: nuovo endpoint API

1. Aggiungi rotta in `routes/api.php`:

```php
use App\Http\Controllers\ProductController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/products/trending', [ProductController::class, 'trending'])
         ->middleware('throttle:api');
});
```

2. Nel controller, usa il pattern `errorResponse()`:

```php
public function trending(Request $request)
{
    try {
        $products = Product::withCount('ratings')
            ->orderByDesc('ratings_count')
            ->paginate(20);

        return response()->json([
            'data' => $products,
        ]);
    } catch (\Throwable $e) {
        return $this->errorResponse(
            code: 'TRENDING_ERROR',
            message: 'Impossibile caricare i prodotti trend',
            status: 500,
            details: ['exception' => $e->getMessage()]
        );
    }
}
```

---

## Frontend: componenti e pagine

### Struttura pagine Inertia

Ogni pagina in `resources/js/Pages/` corrisponde a una rotta in `routes/web.php`. I dati da backend a frontend viaggiano come props Inertia:

```php
// Controller
return Inertia::render('Product/List', [
    'products' => ProductResource::collection($products),
    'lists' => $lists,
]);
```

### Layout

- `AuthenticatedLayout.vue` — wrapper per pagine autenticate (nav + contenuto)
- `GuestLayout.vue` — wrapper per pagine pubbliche

### Componenti riutilizzabili

Tutti i componenti UI generici sono in `resources/js/Components/`. Prima di crearne uno nuovo, verifica che esista già un componente simile.

### Composable

La logica condivisa frontend va in `resources/js/composables/`. Esempio: `useImageCropper.ts`.

---

## Testing

### Eseguire i test

```bash
# Tutti i test
vendor/bin/sail artisan test

# Test specifico
vendor/bin/sail artisan test --filter=ProductImageUpdateTest

# Con copertura
vendor/bin/sail artisan test --coverage
```

### Struttura test

- **Feature tests**: `tests/Feature/` — testano flussi completi (HTTP → DB → risposta)
- **Unit tests**: `tests/Unit/` — testano logica isolata (modelli, servizi)

### Creare un nuovo test

```bash
# Feature test
vendor/bin/sail artisan make:test ProductFeatureTest

# Unit test
vendor/bin/sail artisan make:test --unit ProductServiceTest
```

### Pattern per feature test

```php
public function test_user_can_create_product(): void
{
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/product', [
        'barcode' => '8004702812345',
        'name' => 'Pasta Barilla',
    ]);

    $response->assertStatus(201)
             ->assertJsonStructure(['product' => ['id', 'barcode', 'name']]);

    $this->assertDatabaseHas('products', [
        'barcode' => '8004702812345',
    ]);
}
```

---

## Deployment

### Requisiti

- PHP 8.4+
- MySQL/PostgreSQL o SQLite
- Node.js 18+
- Composer

### Comandi build

```bash
# Installa dipendenze PHP
vendor/bin/sail composer install --no-dev --optimize-autoloader

# Installa dipendenze Node
vendor/bin/sail npm ci --production

# Build asset
vendor/bin/sail npm run build

# Migrazioni
vendor/bin/sail artisan migrate --force

# Cache configurazione
vendor/bin/sail artisan config:cache
vendor/bin/sail artisan route:cache
vendor/bin/sail artisan view:cache
```

### Variabili ambiente

Vedi `.env.example` per la lista completa. Le variabili critiche:

| Variabile | Descrizione |
|-----------|-------------|
| `APP_ENV` | `production` in deploy |
| `APP_DEBUG` | `false` in produzione |
| `DB_CONNECTION` | Tipo database |
| `REDIS_HOST` | Host Redis per cache/queue |
| `SANCTUM_STATEFUL_DOMAINS` | Domini per cookie Sanctum (SPA) |

---

## Estensioni comuni

### Aggiungere un nuovo tipo di entità

1. Modello + migrazione + factory
2. Controller + Form Request
3. Rotte web + API
4. Pagina/componetente Vue
5. Test feature + unit

### Integrare un servizio esterno

1. Creare classe in `app/Services/`
2. Registrare in `.env` le credenziali
3. Usare dependency injection nel controller
4. Mockare nei test

### Aggiungere filtri/ordinamenti

1. Estendi il metodo `index()` nel controller
2. Aggiungi parametri query validati
3. Aggiorna la query Eloquent
