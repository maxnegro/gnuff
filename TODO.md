# TODO: Miglioramenti Interfaccia Utente e Ottimizzazione Codice

## Piano Implementazione: Upload e Crop Immagini Prodotto

### FASE 1: Backend CRUD ✓
#### Step 1.1: Preparazione e validazione ✓
- [x] Analizzato modello Product (campo `image_url`)
- [x] Analizzato ProductImageCacheService per pattern di percorso
- [x] Creato UpdateProductImageRequest con validazione Base64
- [x] Definiti formati supportati: JPEG, PNG, WebP
- [x] Impostato limite massimo: 5MB
- [x] Implementata protezione CSRF

#### Step 1.2: Controller action updateImage() ✓
- [x] Creato endpoint POST /product/{barcode}/image
- [x] Implementato parsing della URI Base64 
- [x] Creato metodo normalizeImageContent() con rimozione EXIF
- [x] Creato metodo saveImageToStorage() con percorsi a due livelli
- [x] Integrato Product create/update logic
- [x] Configurate risposte JSON
- [x] Applicato Pint formatting

#### Step 1.3: Test backend ✓
- [x] Test: valida Base64 image upload
- [x] Test: rimpiazza immagine esistente
- [x] Test: crea prodotto se non esiste
- [x] Test: rifiuta Base64 non valida
- [x] Test: rifiuta immagine troppo grande
- [x] Test: rifiuta formato non supportato
- [x] Test: richiede autenticazione

### FASE 2: Frontend - In Progress
#### Step 2.1: Composable useImageCropper [✓]
- [x] Creare `resources/js/composables/useImageCropper.ts`
- [x] Implementare canvas 2D per rendering immagine
- [x] Implementare resize/crop logic
- [x] Implementare zoom e pan controls
- [x] Implementare conversione in Base64

#### Step 2.2: ImageCropModal component [✓]
- [x] Creare `resources/js/Pages/Components/ImageCropModal.vue`
- [x] Canvas per preview e crop
- [x] Zoom slider control
- [x] Pulsanti Confirm/Cancel
- [x] Overlay semi-trasparente

#### Step 2.3: ProductRatingModal integration [✓]
- [x] Aggiungere pulsante "Cambia Immagine" a ProductRatingModal
- [x] File input hidden per upload da dispositivo
- [x] Camera input per foto da fotocamera (mobile)
- [x] Trigger ImageCropModal
- [x] Implementare POST a /product/{barcode}/image
- [x] Aggiornare UI con immagine dopo upload

#### Step 2.4: Component tests [ ]
- [ ] Test: ImageCropModal rendering
- [ ] Test: Canvas zoom e pan
- [ ] Test: Base64 conversion
- [ ] Test: ProductRatingModal integration

### FASE 3: Integration
#### Step 3.1: End-to-end test [ ]
- [ ] Test: upload → crop → POST → UI update
- [ ] Test: image replacement workflow
- [ ] Test: error handling completo

#### Step 3.2: Manual testing [ ]
- [ ] Testare su desktop (Chrome, Firefox, Safari)
- [ ] Testare su mobile (iOS, Android)
- [ ] Testare con diverse dimensioni immagine
- [ ] Testare performance con immagini grandi

---

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
- [x] **Rifattorizzare componenti Vue**
  - [x] Scomporre componenti grandi in componenti più piccoli e riutilizzabili
  - [x] Applicare pattern di design (es. composable per logica condivisa)
  - [x] Documentare composables e componenti complessi

- [x] **Ottimizzare le chiamate API**  
  - [x] Implementare il caching delle risposte API con Redis  
  - [x] Ridurre il numero di chiamate API tramite batching  
  - [x] Aggiungere rate limiting per proteggere l'API  

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
  - [x] Testare il comportamento su dispositivi mobili  

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
Aggiungere all'attuale modulo di valutazione prodotto la possibilità di sostituire l'immagine prodotto eventualmente esistente caricando una nuova immagine da file locale o fotocamera, ritagliandola in formato quadrato e ridimensionandola prima del salvataggio.

**Modulo interessato:** `resources/js/Components/ProductRatingModal.vue`

### Riferimenti di implementazione

#### Ambito funzionale
- La funzionalità deve essere integrata nel flusso già esistente della modale di valutazione prodotto
- L'utente può sostituire l'immagine prodotto corrente dalla sezione dati prodotto
- Il caricamento avviene tramite selezione file o fotocamera (dove supportato)
- Dopo la selezione, modale di crop in rapporto 1:1
- L'immagine ritagliata viene ridimensionata in thumbnail quadrata compatta
- La nuova immagine sostituisce quella eventualmente già presente (non galleria separata)
- Il salvataggio aggiorna il prodotto mantenendo coerenti nome, barcode e valutazione
- L'input manuale per URL immagine rimane disponibile come alternativa

#### Requisiti frontend
- Mostrare immagine prodotto corrente o placeholder
- Pulsante dedicato per avviare cambio immagine dalla preview
- Interfaccia per inserimento URL immagine E caricamento locale
- Modale di crop con canvas e zoom slider
- Output immagine quadrata ridimensionata e pronta per upload
- Loading state durante salvataggio
- Messaggio di errore chiaro; stato attuale mantenuto in caso di fallimento
- Preview aggiornata immediatamente dopo salvataggio riuscito

#### Requisiti backend
- Accettare immagine Base64 già ritagliata e ridimensionata dal frontend
- Validare che il payload sia un'immagine valida (jpg, png, webp)
- Salvare in storage locale pubblico
- Aggiornare Product model (sostituire valore immagine precedente)
- Gestire caso: nessuna immagine precedente
- Restituire URL utilizzabile dalla UI
- Proteggere da payload troppo grandi (max 5MB), formati non supportati, richieste non valide
- Validare lato server OGNI immagine ricevuta
- Gestire EXIF e normalizzare immagine
- Rimuovere/neutralizzare metadati sensibili
- CSRF protection e autorizzazione coerente

---

### Piano di implementazione

#### FASE 1: BACKEND - Fondamenti

**Step 1.1: Preparazione e validazione** [✓]
- [x] Analizzare Product model e campo `image_url` (è `image_url`, non `image`)
- [x] Definire regole validazione immagine: formati (jpg, png, webp), max 5MB
- [x] Creare `UpdateProductImageRequest` con validazioni CSRF e autorizzazione

**Step 1.2: Controller action** [✓]
- [x] Creare action `updateProductImage` in Products controller
- [x] Ricevere immagine Base64, validare, salvare in storage pubblico
- [x] Gestire EXIF, normalizzare immagine (rimuovere metadati)
- [x] Aggiornare Product model
- [x] Restituire URL nuova immagine

**Step 1.3: Test backend** [✓]
- [x] Test endpoint con immagine valida
- [x] Test sostituzione immagine esistente
- [x] Test caso senza immagine precedente
- [x] Test validazioni (formato, dimensione, errori)

#### FASE 2: FRONTEND - Componenti

**Step 2.1: Composables e utility** [✓]
- [x] Creare `composable useImageCropper.ts` (canvas 2D, resize, base64)
- [x] Creare `utils/imageFileValidation.ts` (check tipo, dimensione)
- [x] Creare `utils/imageConverter.ts` (file → canvas → base64)

**Step 2.2: Modale di crop** [✓]
- [x] Creare componente `ImageCropModal.vue`
  - [x] Canvas per preview
  - [x] Slider per zoom
  - [x] Pulsanti confirm/cancel
  - [x] Output immagine quadrata ridimensionata (es. 400x400px)

**Step 2.3: Integrazione in ProductRatingModal** [✓]
- [x] Aggiungere sezione cambio immagine (preview + button)
- [x] File input per desktop + fotocamera per mobile
- [x] Collegare a ImageCropModal
- [x] Loading state durante upload
- [x] Gestire errori e messaggi utente
- [x] Update preview dopo salvataggio

**Step 2.4: Test frontend** [✓]
- [x] Test apertura/chiusura ImageCropModal
- [x] Test upload file desktop
- [x] Test caricamento fotocamera (mock)
- [x] Test ridimensionamento immagine
- [x] Test invio base64 al backend
- [x] Test aggiornamento preview post-salvataggio

#### FASE 3: Integrazione & Verifica

**Step 3.1: Connessione end-to-end** [✓]
- [x] Verificare flusso completo: upload → crop → backend → UI
- [x] Test errore backend → messaggio utente
- [x] Test rollback se errore (immagine precedente rimane visibile)

**Step 3.2: Test manuale cross-device** [✓]
- [x] Desktop: upload file
- [x] Mobile: fotocamera + file
- [x] Immagini landscape, portrait, quadrate
- [x] Browser testing su dispositivi diversi

---

### Criteri di accettazione
- [x] Piano di implementazione definito e approvato
- [x] La funzionalità è integrata nell'attuale modulo di valutazione prodotto
- [x] L'utente può sostituire l'immagine prodotto da file o fotocamera
- [x] Dopo il crop, l'immagine viene ridimensionata in formato quadrato (400x400px)
- [x] La nuova immagine sostituisce quella eventualmente esistente
- [x] Dopo il salvataggio, la preview prodotto mostra la nuova immagine
- [x] Gli errori non cancellano l'immagine precedente
- [x] Il backend salva solo immagini valide e controllate
- [x] Il flusso non richiede modifiche allo schema database
- [x] Test backend e frontend passano
- [x] Verificato su desktop e mobile


### Strategie di sicurezza e rollback
- Backend valida OGNI immagine prima di salvare
- Immagine precedente NON aggiornata finché backend non conferma successo
- In caso di errore backend, immagine precedente rimane visibile e valida nel modulo
- Metadati sensibili rimossi prima del salvataggio
- Protezione CSRF e autorizzazione coerente con contesto prodotto
