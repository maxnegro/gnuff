# gnuff - Database di Prodotti Alimentari con Valutazioni Utente

## Descrizione del Progetto
Gnuff è un'applicazione Laravel 13 che permette di mantenere un database di prodotti alimentari, assegnando a ciascuno un gradimento basato sull'esperienza personale dell'utente. Il sistema consente di valutare l'acquisto di prodotti quando si trova al supermercato, grazie a un'interfaccia intuitiva e a un database ben organizzato.

## Stato Attuale dell'Applicazione
- **Backend**: Laravel 13 con Sanctum per l'autenticazione API.
- **Frontend**: Inertia.js + Vue 3, gestito tramite Vite.
- **Database**: SQLite di default, con supporto per MySQL/PostgreSQL.
- **Container**: Docker (Sail) per l'ambiente di sviluppo.
- **Funzionalità operative**:
  - Gestione di liste di prodotti.
  - Visualizzazione, creazione, aggiornamento e cancellazione di prodotti.
  - Valutazione dei prodotti con rating (`gnuf`, `ok`, `meh`, `bleah`).
  - Integrazione con l'API di **OpenFoodFacts** per recuperare informazioni sui prodotti tramite barcode.
  - API RESTful protette da Sanctum per operazioni su prodotti, liste e rating.
  - Interfaccia web SPA con Inertia.js per una UX fluida.

## Tecnologie Utilizzate
- **Backend**: Laravel 13 (PHP 8.3+), Laravel Sanctum, Inertia.js
- **Frontend**: Inertia.js + Vue 3, Ziggy, Tailwind CSS
- **Sviluppo**: Docker (Sail), Vite, npm
- **Database**: SQLite (di default), supporto per MySQL/PostgreSQL
- **Testing**: PHPUnit, Pest, Collision

## Script Utili
- `./vendor/bin/sail up -d` – Avvia l'ambiente di sviluppo completo (server, queue, log, Vite).
- `./vendor/bin/sail artisan migrate` – Esegue le migrazioni.
- `./vendor/bin/sail npm run dev` – Compila le viste Vue in modalità sviluppo.
- `./vendor/bin/sail npm run build` – Compila le viste Vue per produzione.

## API Utilizzate
### OpenFoodFacts
- **Endpoint**: `https://world.openfoodfacts.org/api/v0/product/{barcode}.json`
- **Scopo**: Recuperare nome, immagine e altre informazioni nutrizionali di un prodotto a partire dal barcode.
- **Utilizzo**: Il servizio `App\Services\OpenFoodFactsService` effettua la chiamata e restituisce i dati al `ProductController`.

### API interne (protette da Sanctum)
| Metodo | URL | Controller | Descrizione |
|--------|-----|------------|-------------|
| `GET` | `/api/products` | `ProductController@apiIndex` | Lista paginata di tutti i prodotti (opzionale filtro per `list_id`). |
| `GET` | `/api/product/{barcode}` | `ProductController@show` | Dettagli di un prodotto, con integrazione OpenFoodFacts. |
| `PUT` | `/api/product/{barcode}` | `ProductController@update` | Aggiorna nome e immagine di un prodotto. |
| `DELETE` | `/api/product/{product}` | `ProductController@destroy` | Rimuove un prodotto. |
| `POST` | `/api/product` | `ProductController@store` | Crea o aggiorna un prodotto. |
| `POST` | `/api/rate` | `RatingController@store` | Salva un rating per il prodotto nella lista attiva. |
| `GET` | `/api/user/ratings` | `RatingController@userRatings` | Recupera gli ultimi rating dell'utente. |
| `GET` | `/api/ratings` | `RatingController@index` | Lista paginata di rating (filtrabile per `list_id`). |
| `PUT` | `/api/rating/{rating}` | `RatingController@update` | Aggiorna un rating esistente. |
| `DELETE` | `/api/rating/{rating}` | `RatingController@destroy` | Elimina un rating. |
| `GET` | `/api/lists/active-and-all` | `ProductListController@activeAndAll` | Restituisce la lista attiva e tutte le liste dell'utente. |
| `GET` | `/api/lists` | `ProductListController@index` | Lista tutte le liste dell'utente. |
| `POST` | `/api/lists` | `ProductListController@store` | Crea una nuova lista. |
| `PUT` | `/api/lists/{productList}` | `ProductListController@update` | Aggiorna una lista esistente. |
| `DELETE` | `/api/lists/{productList}` | `ProductListController@destroy` | Elimina una lista. |
| `POST` | `/api/lists/{productList}/invite` | `ProductListController@invite` | Invia un invito a un altro utente. |
| `POST` | `/api/lists/{productList}/accept` | `ProductListController@acceptInvite` | Accetta un invito a una lista. |
| `POST` | `/api/lists/{productList}/decline` | `ProductListController@declineInvite` | Rifiuta un invito a una lista. |
| `POST` | `/api/lists/{productList}/active` | `ProductListController@setActive` | Imposta la lista attiva per la sessione. |

> **Nota**: Tutte le rotte API sono prefissate con `/api` e richiedono un token Sanctum valido.

## Database
- File SQLite preconfigurato (`database/database.sqlite`).
- Migrazioni per tabelle base (utenti, prodotti, rating, liste).

## Test
- Esegui `./vendor/bin/sail artisan test` per lanciare i test unitari e di integrazione.

--- 

*Questo README è stato aggiornato per riflettere lo stato corrente dell’applicazione e le API disponibili.*