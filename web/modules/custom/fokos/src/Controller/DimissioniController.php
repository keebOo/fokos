<?php

namespace Drupal\fokos\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\node\Entity\Node;
use Drupal\Core\Datetime\DrupalDateTime;

class DimissioniController extends ControllerBase {

  public function dimettiOspite($ospite_id, $struttura_id) {
    try {
      // 1. Carica il nodo entrata_uscita associato
      $query = \Drupal::entityQuery('node')
        ->condition('type', 'entrate_uscite')
        ->condition('field_ref_ospite', $ospite_id)
        ->condition('field_ref_struttura', $struttura_id)
        ->condition('field_eo_data_out', NULL, 'IS NULL')
        ->accessCheck(FALSE)
        ->execute();
        
      if ($nids = array_values($query)) {
        // 2. Aggiorna la data di uscita nel nodo entrata_uscita
        $entrata_uscita = Node::load($nids[0]);
        $today = new DrupalDateTime('now');
        $entrata_uscita->set('field_eo_data_out', $today->format('Y-m-d'));
        $entrata_uscita->save();
        
        // 3. Rimuovi l'ospite dalla lista degli ospiti della struttura
        $struttura = Node::load($struttura_id);
        if ($struttura) {
          $ospiti = $struttura->get('field_refs_ospite')->getValue();
          $ospiti = array_filter($ospiti, function($item) use ($ospite_id) {
            return $item['target_id'] != $ospite_id;
          });
          $struttura->set('field_refs_ospite', $ospiti);
          $struttura->save();
        }
        
        $this->messenger()->addStatus($this->t('Ospite dimesso con successo.'));
      } else {
        $this->messenger()->addError($this->t('Nessun record di entrata/uscita trovato per questo ospite in questa struttura.'));
      }
    }
    catch (\Exception $e) {
      \Drupal::logger('fokos')->error($e->getMessage());
      $this->messenger()->addError($this->t('Si è verificato un errore durante la dimissione dell\'ospite.'));
    }
    
    // Redirect alla pagina di provenienza o alla pagina della struttura
    $destination = \Drupal::request()->query->get('destination');
    if ($destination) {
      return new RedirectResponse($destination);
    }
    return $this->redirect('entity.node.canonical', ['node' => $struttura_id]);
  }
} 