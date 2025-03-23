# Documentazione Funzioni

## fokos_theme_suggestions_page_title_alter

**Firma:**
```php
function fokos_theme_suggestions_page_title_alter(array &$suggestions, array $variables)
```

**Descrizione:**
Modifica i suggerimenti del titolo della pagina in base al tipo di nodo.

**Casistiche:**
- Se il percorso è relativo a un'entità nodo, aggiunge un suggerimento basato sul bundle del nodo.

**Chiama:**
Nessuna funzione interna.

---

## fokos_form_alter

**Firma:**
```php
function fokos_form_alter(&$form, \Drupal\Core\Form\FormStateInterface $form_state, $form_id)
```

**Descrizione:**
Modifica i form specifici nascondendo il titolo e aggiungendo validazioni.

**Casistiche:**
- Se il form è tra quelli specificati, nasconde il campo titolo.
- Se è un form di "entrate_uscite", aggiunge la validazione dell'ospite.
- Se è un form di update, disabilita alcuni campi.

**Chiama:**
- `fokos_ospite_validazione`

---

## fokos_node_presave

**Firma:**
```php
function fokos_node_presave(Node $node)
```

**Descrizione:**
Esegue azioni prima del salvataggio di un nodo.

**Casistiche:**
- Se è un nodo nuovo:
  - Se è un "ospite", imposta il titolo.
  - Se è un "entrate_uscite", processa l'entrata/uscita.

**Chiama:**
- `set_ospite_title`
- `process_entrata_uscita`

---

## set_ospite_title

**Firma:**
```php
function set_ospite_title(Node $node)
```

**Descrizione:**
Genera un titolo univoco per l'ospite basato su nome, cognome e timestamp.

**Chiama:**
Nessuna funzione interna.

---

## process_entrata_uscita

**Firma:**
```php
function process_entrata_uscita(Node $node)
```

**Descrizione:**
Gestisce l'inserimento dell'ospite nella struttura e crea il primo soggiorno.

**Casistiche:**
- Se l'ospite e la struttura sono validi:
  - Aggiunge l'ospite alla struttura.
  - Se la data di entrata è oggi, crea il soggiorno.

**Chiama:**
- `aggiungi_ospite_a_struttura`
- `crea_soggiorno_giornaliero`

---

## aggiungi_ospite_a_struttura

**Firma:**
```php
function aggiungi_ospite_a_struttura($struttura_id, $ospite_id)
```

**Descrizione:**
Aggiunge un ospite alla lista della struttura.

**Chiama:**
Nessuna funzione interna.

---

## crea_soggiorno_giornaliero

**Firma:**
```php
function crea_soggiorno_giornaliero(Node $entrata_uscita, $data_in)
```

**Descrizione:**
Crea un nodo soggiorno per il giorno specificato.

**Chiama:**
Nessuna funzione interna.

---

## validazione_ospite

**Firma:**
```php
function validazione_ospite(&$form, \Drupal\Core\Form\FormStateInterface $form_state)
```

**Descrizione:**
Valida se l'ospite è già assegnato a una struttura.

**Chiama:**
- `is_ospite_gia_assegnato`

---

## is_ospite_gia_assegnato

**Firma:**
```php
function is_ospite_gia_assegnato($ospite_id)
```

**Descrizione:**
Controlla se un ospite è già assegnato a una struttura attiva.

**Chiama:**
Nessuna funzione interna.

---

## fokos_entity_insert

**Firma:**
```php
function fokos_entity_insert(\Drupal\Core\Entity\EntityInterface $entity)
```

**Descrizione:**
Genera soggiorni quando viene inserita un'entrata/uscita.

**Chiama:**
- `genera_soggiorni`

---

## genera_soggiorni

**Firma:**
```php
function genera_soggiorni(Node $entrata_uscita)
```

**Descrizione:**
Genera nodi soggiorno per ogni giorno tra entrata e uscita.

**Chiama:**
- `crea_soggiorno_giornaliero`

---

## fokos_entity_presave

**Firma:**
```php
function fokos_entity_presave(\Drupal\Core\Entity\EntityInterface $entity)
```

**Descrizione:**
Sincronizza i soggiorni prima del salvataggio.

**Chiama:**
- `sincronizza_soggiorni`

---

## sincronizza_soggiorni

**Firma:**
```php
function sincronizza_soggiorni($entrata_uscita, $old_data_in, $old_data_out, $new_data_in, $new_data_out)
```

**Descrizione:**
Allunga o accorcia i soggiorni in base alle nuove date.

**Chiama:**
- `crea_soggiorno_giornaliero`
- `elimina_soggiorni`

---

## elimina_soggiorni

**Firma:**
```php
function elimina_soggiorni($entrata_uscita, $start_date, $end_date)
```

**Descrizione:**
Elimina soggiorni superflui in base alle date.

**Chiama:**
Nessuna funzione interna.
