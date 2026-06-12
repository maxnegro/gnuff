# TODO: Miglioramenti Interfaccia Utente e Ottimizzazione Codice

## Obiettivi
- Migliorare l'estetica e l'usabilità dell'interfaccia utente
- Ottimizzare l'efficienza del codice e la manutenibilità
- Implementare funzionalità avanzate per un'esperienza più fluida

## Task

### 1. Analisi e Ottimizzazione UI
- [ ] **Rivedere il layout principale**
  - [x] Test di copertura layout principale
  - [x] Introdurre un design system globale base (palette, superfici, pulsanti, campi)
  - [x] Applicare principi di design armonico (spaziatura, colori, tipografia)
  - [x] Allineare layout principali e welcome page a un primo refactor visivo coerente
  - [x] Allineare Scanner, Prodotti, Liste e modale prodotto al design system base
  - [x] Prevedere per tutte le viste il supporto per tema light/dark di sistema
  - [x] Attivare il supporto light/dark di sistema su layout principali e welcome page
  - [x] Estendere il supporto light/dark alle viste applicative principali
  - [x] Ottimizzare la responsività per dispositivi mobili
  - [x] Aggiungere animazioni sottili per interazioni utente

### 2. Ottimizzazione Codice
- [ ] **Rifattorizzare componenti Vue**
  - [ ] Scomporre componenti grandi in componenti più piccoli e riutilizzabili
  - [ ] Applicare pattern di design (es. composable per logica condivisa)
  - [ ] Documentare composables e componenti complessi

- [ ] **Ottimizzare le chiamate API**  
  - [ ] Implementare il caching delle risposte API con Redis  
  - [ ] Ridurre il numero di chiamate API tramite batching  
  - [ ] Aggiungere rate limiting per proteggere l'API  

- [x] **Migliorare la gestione degli errori**  
  - [x] Implementare un sistema di errori personalizzato con messaggi chiari  
  - [x] Aggiungere logging dettagliato per debug  
  - [x] Ottimizzare la gestione delle eccezioni per evitare crash  

### 3. Test e Qualità
- [ ] **Aggiungere test unitari per componenti UI**  
  - [x] Impostare suite Vitest eseguibile con Sail per Vue 3 + Inertia  
  - [x] Testare layout e componenti chiave iniziali (layout, welcome, input)  
  - [x] Testare il rendering di componenti chiave aggiuntivi (lista prodotti, form di valutazione, pagina liste)  
  - [x] Proteggere il flusso scanner (pagina scanner + emissione barcode dal componente base)  
  - [ ] Testare il comportamento su dispositivi mobili  

- [ ] **Implementare test di prestazioni**  
  - [ ] Misurare il tempo di caricamento delle pagine  
  - [ ] Testare il comportamento sotto carico (es. 1000 prodotti)  

### 4. Documentazione e Manutenzione
- [ ] **Aggiornare la documentazione**  
  - [ ] Documentare le API RESTful con esempi di utilizzo  
  - [ ] Aggiungere guide per sviluppatori su come estendere il sistema  

### 5. Sicurezza e Accessibilità
- [ ] **Migliorare l'accessibilità**  
  - [ ] Aggiungere ARIA labels per componenti interattivi  
  - [ ] Implementare il supporto per lettori schermo  
  - [ ] Verificare il contrasto dei colori per utenti con disabilità visiva  

- [ ] **Aggiungere protezioni di sicurezza**  
  - [ ] Implementare CSRF protection per tutte le richieste  
  - [ ] Aggiungere validazione avanzata per input utente  
  - [ ] Implementare protezione da XSS  

### 6. Funzionalità Avanzate
- [ ] **Migliorare la pagina dei prodotti**  
  - [ ] Implementare un sistema di filtri avanzati (categoria, prezzo, valutazione)  
  - [X] Aggiungere un sistema di ordinamento dinamico (per nome, data, punteggio)  
  - [ ] Ottimizzare il caricamento delle immagini con lazy loading  

- [ ] **Aggiungere un sistema di ricerca avanzato**  
  - [ ] Implementare un motore di ricerca full-text  
  - [ ] Aggiungere filtri per attributi personalizzati (es. allergeni, ingredienti)  

- [ ] **Implementare un sistema di notifiche**  
  - [ ] Aggiungere notifiche in tempo reale per nuovi prodotti o aggiornamenti  
  - [ ] Implementare un sistema di notifiche push (es. tramite Pusher)  

- [ ] **Ottimizzare l'esperienza di onboarding**  
  - [ ] Creare un tutorial interattivo per nuovi utenti  
  - [ ] Aggiungere un sistema di suggerimenti per funzionalità avanzate

- [ ] **Integrazione con API OpenFoodFacts.net**
  - [ ] Possibilità di visualizzare dati nutrizionali e altre info dettagliate sui prodotti
  - [ ] Sezione dedicata per inviare immagini e proporre correzioni su prodotti incompleti

## Priorità
- [ ] Task critici (es. miglioramenti UI principali)  
- [ ] Task di ottimizzazione (es. caching, performance)  
- [ ] Task di manutenzione (es. documentazione, test)  

## Note
- Utilizzare `./vendor/bin/sail` per eseguire comandi Laravel  
- Verificare sempre le modifiche con `./vendo/bin/sail npm run build` e `./vendor/bin/sail artisan migrate`  
- Mantenere il codice conforme alle convenzioni Laravel e Inertia.js

---

## Piano: Upload + Crop Immagini Prodotto nel modulo valutazione

### Obiettivo
Aggiungere all’attuale modulo di valutazione prodotto la possibilità di sostituire l’immagine prodotto eventualmente esistente caricando una nuova immagine da file locale o fotocamera, ritagliandola in formato quadrato e ridimensionandola prima del salvataggio.

### Modulo interessato
- Modulo valutazione prodotto: `resources/js/Components/ProductRatingModal.vue`

### Ambito funzionale
- La funzionalità deve essere integrata nel flusso già esistente della modale di valutazione prodotto.
- L’utente deve poter sostituire l’immagine prodotto corrente dalla sezione dati prodotto.
- Il caricamento può avvenire tramite selezione file o fotocamera, dove supportato dal dispositivo.
- Dopo la selezione, l’utente deve poter ritagliare l’immagine in rapporto 1:1.
- L’immagine ritagliata deve essere ridimensionata in una thumbnail quadrata compatta prima del salvataggio.
- La nuova immagine deve sostituire quella eventualmente già presente, non aggiungere una galleria separata.
- Il salvataggio deve aggiornare il prodotto corrente mantenendo coerenti nome, barcode e valutazione in corso.
- L’input manuale per URL immagine può rimanere disponibile come alternativa al caricamento locale.

### Requisiti frontend
- Il modulo deve mostrare l’immagine prodotto corrente o il placeholder quando l’immagine non è disponibile.
- L’utente deve poter avviare il cambio immagine dalla preview o da un pulsante dedicato.
- L’interfaccia deve consentire sia l’inserimento di un URL immagine sia il caricamento locale.
- Dopo la scelta di un file o di una foto da fotocamera, deve aprirsi una modale di crop.
- Il crop deve essere quadrato e deve produrre un’immagine finale ridimensionata e pronta per l’upload.
- Durante il salvataggio deve essere mostrato uno stato di caricamento.
- In caso di errore, il modulo deve mantenere lo stato corrente e mostrare un messaggio chiaro.
- Dopo il salvataggio riuscito, la preview prodotto deve mostrare immediatamente la nuova immagine.

### Requisiti backend
- Il backend deve accettare la nuova immagine già ritagliata e ridimensionata dal frontend.
- Deve validare che il payload ricevuto sia un’immagine valida.
- Deve salvare l’immagine in storage locale pubblico.
- Deve aggiornare il prodotto sostituendo il valore immagine precedente.
- Deve gestire correttamente il caso in cui il prodotto non abbia ancora un’immagine.
- Deve restituire l’URL utilizzabile dalla UI.
- Deve proteggere il salvataggio da payload troppo grandi, formati non supportati o richieste non valide.

### Regole di sostituzione
- La nuova immagine caricata sostituisce sempre l’immagine prodotto corrente.
- Se esiste già un’immagine precedente, questa non deve più essere considerata immagine principale dopo il salvataggio riuscito.
- Se il salvataggio fallisce, l’immagine precedente deve rimanere visibile e valida nel modulo.
- Il flusso non deve richiedere modifiche allo schema database se può essere gestito con il campo immagine prodotto esistente.

### Sicurezza e qualità
- Validare lato server ogni immagine ricevuta.
- Limitare dimensione massima e formati accettati.
- Gestire orientamento EXIF e normalizzare l’immagine prima del salvataggio.
- Evitare di fidarsi esclusivamente del risultato lato client.
- Prevedere protezione CSRF e autorizzazione coerente con il contesto prodotto.
- Rimuovere o neutralizzare metadati sensibili quando possibile.

### Test richiesti
- Test frontend del flusso di apertura cambio immagine dal modulo valutazione.
- Test del caricamento file/fotocamera e della modale di crop.
- Test del ridimensionamento in thumbnail quadrata.
- Test backend del salvataggio immagine con storage fake.
- Test di sostituzione immagine esistente.
- Test del caso senza immagine precedente.
- Test di immagini non valide, troppo grandi o con formato non supportato.
- Verifica manuale su desktop e mobile.
- Verifica con immagini landscape, portrait e già quadrate.

### Criteri di accettazione
- La funzionalità è integrata nell’attuale modulo di valutazione prodotto.
- L’utente può sostituire l’immagine prodotto da file o fotocamera.
- Dopo il crop, l’immagine viene ridimensionata in formato quadrato.
- La nuova immagine sostituisce quella eventualmente esistente.
- Dopo il salvataggio, la preview prodotto mostra la nuova immagine.
- Gli errori non cancellano l’immagine precedente.
- Il backend salva solo immagini valide e controllate.
- Il flusso non richiede modifiche allo schema database.

### Rollback
- Prevedere una strategia di rollback per tornare all’immagine precedente in caso di salvataggio fallito o comportamento non desiderato.
- Non aggiornare l’immagine principale finché il salvataggio non è confermato.
