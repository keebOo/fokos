# Note di Sviluppo

## TODO

### Aggiornamento Riferimenti Soggiorni
Il seguente codice deve essere implementato per gestire i soggiorni senza riferimenti:

```php
/**
 * Aggiorna i riferimenti mancanti nei soggiorni.
 */
public function aggiornaSoggiorniSenzaRiferimento(NodeInterface $entrata_uscita): void {
   $query = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', 'soggiorno')
      ->condition('field_ref_ospite', $entrata_uscita->get('field_ref_ospite')->target_id)
      ->condition('field_ref_struttura', $entrata_uscita->get('field_ref_struttura')->target_id)
      ->condition('field_ref_entrata_uscita', NULL, 'IS NULL')
      ->accessCheck(FALSE);
   
   $soggiorni_ids = $query->execute();
   if (!empty($soggiorni_ids)) {
      $soggiorni = $this->entityTypeManager->getStorage('node')->loadMultiple($soggiorni_ids);
      foreach ($soggiorni as $soggiorno) {
            $soggiorno->set('field_ref_entrata_uscita', $entrata_uscita);
            $soggiorno->save();
      }
   }
}
```

Questo metodo dovrà essere aggiunto a `SoggiornoService` per gestire i casi in cui i soggiorni esistenti non hanno un riferimento corretto all'entrata/uscita.

## Note Implementative
- Il metodo deve essere integrato con la gestione esistente dei soggiorni
- Aggiungere logging appropriato per tracciare gli aggiornamenti
- Considerare l'aggiunta di un comando Drush per eseguire l'aggiornamento in batch
- Testare con particolare attenzione i casi di soggiorni storici

## Testing Checklist
- [ ] Verificare che i soggiorni vengano correttamente associati
- [ ] Testare con diversi scenari di date
- [ ] Verificare la gestione degli errori
- [ ] Testare le performance con grandi set di dati 