# gnuff - Database di Prodotti Alimentari con Valutazioni Utente

## Descrizione del Progetto
gnuff è un'applicazione Laravel 12 che permette di mantenere un database di prodotti alimentari, assegnando a ciascuno un gradimento basato sull'esperienza personale dell'utente. Questo sistema consente di valutare l'acquisto di prodotti quando si trova al supermercato, grazie a un'interfaccia intuitiva e a un database ben organizzato.

## Tecnologie Utilizzate
- **Backend:** Laravel 12 (PHP 8.2+), Laravel Sanctum (API autenticazione), Inertia.js (frontend)
- **Frontend:** Inertia.js + Ziggy (helper per route), Tailwind CSS
- **Sviluppo:** Docker (Sail), Vite, npm
- **Database:** SQLite (di default), supporto per MySQL/PostgreSQL
- **Testing:** PHPUnit, Pest, Collision

## Funzionalità Principali
1. **Database di Prodotti:** Gestione di prodotti alimentari con attributi personalizzabili.
2. **Valutazioni Utente:** Assegnazione di gradimento basato sull'esperienza individuale.
3. **API RESTful:** Accesso ai dati tramite endpoint sicuri con Sanctum.
4. **Interfaccia Web:** Applicazione single-page con Inertia.js per un'esperienza fluida.
5. **Sviluppo Locale:** Ambiente Docker preconfigurato per avvio rapido.

## Script Utili
- `composer run dev`: Avvia ambiente di sviluppo completo (server, queue, log, Vite).
- `php artisan serve`: Avvia il server di sviluppo.
- `npm run dev`: Avvia Vite in modalità di sviluppo.

## Database
- File SQLite preconfigurato (`database/database.sqlite`).
- Migrations per tabelle base (utenti, prodotti, valutazioni).

Questo progetto è pronto per lo sviluppo di un'applicazione web moderna per gestire e valutare prodotti alimentari in modo semplice ed efficace.