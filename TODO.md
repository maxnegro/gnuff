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

#### Step 2.5: Mobile-First UX Enhancements [ ]
- [ ] Aggiungere feedback visivo per gesture touch (zoom/pan)
- [ ] Implementare modalità fullscreen per crop su schermi piccoli
- [ ] Aggiungere hint interattivi per pinch-to-zoom
- [ ] Migliorare accessibilità con ARIA labels per controlli canvas
- [ ] Aggiungere keyboard shortcuts per desktop (scroll wheel zoom, arrow pan)
- [ ] Implementare lazy loading per immagini nella ProductPreviewCard
- [ ] Aggiungere skeleton loader per immagini grandi
- [ ] Ottimizzare dimensioni crop per diverse risoluzioni mobile

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
- [x] **Rivedere il layout principale**
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
- [x] **Aggiungere test unitari per componenti UI**  
  - [x] Impostare suite Vitest eseguibile con Sail per Vue 3 + Inertia  
  - [x] Testare layout e componenti chiave iniziali (layout, welcome, input)  
  - [x] Testare il rendering di componenti chiave aggiuntivi (lista prodotti, form di valutazione, pagina liste)  
  - [x] Proteggere il flusso scanner (pagina scanner + emissione barcode dal componente base)  
  - [x] Testare il comportamento su dispositivi mobili  

- [ ] **Implementare test di prestazioni**  
  - [ ] Misurare il tempo di caricamento delle pagine  
  - [ ] Testare il comportamento sotto carico (es. 1000 prodotti)  

### 4. Documentazione e Manutenzione
- [x] **Aggiornare la documentazione**  
  - [x] Documentare le API RESTful con esempi di utilizzo  
  - [x] Aggiungere guide per sviluppatori su come estendere il sistema  

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

## Strategie di sicurezza e rollback
- Backend valida OGNI immagine prima di salvare
- Immagine precedente NON aggiornata finché backend non conferma successo
- In caso di errore backend, immagine precedente rimane visibile e valida nel modulo
- Metadati sensibili rimossi prima del salvataggio
- Protezione CSRF e autorizzazione coerente con contesto prodotto

---

## 📱 Mobile-First Implementation Analysis

### Current State Assessment

**Backend (Complete ✓)**
- Base64 image validation (5MB limit, JPEG/PNG/WebP only)
- Two-level storage path for images (`products/{xx}/{yy}/`)
- Authentication and rate limiting via `throttle:api` middleware
- Product creation/update logic integrated
- Rollback strategy: previous image preserved on error

**Frontend Status**
- `useImageCropper.ts` composable: Complete with zoom/pan/crop
- `ImageCropModal.vue`: Complete with touch/mouse gestures
- `ProductRatingModal.vue`: Integrated but needs mobile UX improvements
- Tests: Partial coverage, missing image crop tests

### Mobile-First Design Issues Identified

| Component | Issue | Priority |
|-----------|-------|----------|
| ImageCropModal | No fullscreen mode on small screens | High |
| ImageCropModal | Touch feedback lacks visual indication | Medium |
| ProductPreviewCard | No lazy loading for product images | High |
| ProductPreviewCard | No placeholder transition | Medium |
| ProductRatingModal | Form inputs could be larger for touch targets | Medium |
| Scanner page | Camera constraints could cause orientation issues | High |
| All pages | Missing ARIA labels for accessibility | Medium |

### Detailed Implementation Plan

#### Phase 1: Critical Mobile UX Fixes (High Priority)

1. **ImageCropModal Mobile Optimizations**
   - Add `v-bind:class="{'fixed inset-0': isMobile, 'relative max-w-md': !isMobile}"` for fullscreen on mobile
   - Implement responsive canvas sizing (use `window.innerWidth` on mobile)
   - Add visual feedback during touch gestures (scale transform animation)
   - Increase minimum touch target size (>44px) for zoom controls

2. **Image Lazy Loading & Performance**
   - Add `loading="lazy"` to all `<img>` tags in `ProductPreviewCard` and `Product/List.vue`
   - Implement Intersection Observer for progressive image loading
   - Add skeleton loaders using CSS pulse animation for image placeholders

3. **Touch Target Improvements**
   - Increase button padding for camera/file buttons (minimum 44x44px touch area)
   - Add larger hit areas for image change (current: small "Cambia" button)

#### Phase 2: Accessibility & Standards (Medium Priority)

1. **ARIA Implementation**
   - Add `role="slider"` and `aria-label` to zoom slider
   - Add `aria-describedby` to canvas for gesture instructions
   - Add `role="button"` and `tabindex="0"` to ProductPreviewCard image
   - Implement keyboard navigation for crop modal (arrow keys for pan)

2. **Keyboard Shortcuts (Desktop Parity)**
   - Mouse wheel zoom when hovering canvas
   - Arrow keys for fine pan control
   - Enter/Space to confirm crop, Escape to cancel

#### Phase 3: Performance Optimization (High Priority)

1. **Image Handling**
   - Pre-downscale large images before canvas operations (max 2048px)
   - Use `createImageBitmap()` for better performance on mobile
   - Implement WebP compression output option

2. **Memory Management**
   - Revoke object URLs after upload
   - Clear canvas on modal close
   - Add `requestIdleCallback` for non-critical image loading

#### Phase 4: Testing Strategy

| Test File | Mobile Scenarios to Cover |
|-----------|-------------------------|
| `image-crop-modal.test.js` | Touch gesture simulation, fullscreen toggle, responsive canvas |
| `product-rating-modal.test.js` | Image upload flow, camera input, error states |
| `product-list-index.test.js` | Lazy loading, image error fallbacks |
| New: `mobile-viewport.test.js` | Viewport-specific behavior tests |

### Recommended CSS Additions

```css
@media (max-width: 639px) {
  .crop-modal-fullscreen {
    border-radius: 0;
    max-width: 100vw;
    height: 100vh;
  }
  
  .touch-feedback {
    transition: transform 0.1s ease-out;
  }
  
  .touch-active {
    transform: scale(0.95);
  }
}

.skeleton-loader {
  background: linear-gradient(90deg, var(--app-bg-muted) 25%, var(--app-surface) 50%, var(--app-bg-muted) 75%);
  background-size: 200% 100%;
  animation: pulse 1.5s infinite;
}

@keyframes pulse {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}
```

### Implementation Order Recommendation

1. **Week 1**: ImageCropModal mobile fullscreen + lazy loading
2. **Week 2**: Touch gesture visual feedback + ARIA labels
3. **Week 3**: Keyboard shortcuts + performance optimization (pre-downscaling)
4. **Week 4**: Write comprehensive tests
5. **Week 5**: Manual testing on iOS/Android + Safari/Chrome mobile

### Success Metrics

- Image upload flow works seamlessly on mobile (tested on iOS Safari + Android Chrome)
- Canvas crop operations < 100ms on mid-range mobile devices
- No memory leaks when uploading multiple images
- Lighthouse mobile score > 90
- Touch targets meet WCAG 2.1 AA standards (44x44px minimum)
