<?php

namespace Drupal\fokos\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\node\NodeInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Service per la gestione degli ospiti.
 *
 * Gestisce le operazioni relative agli ospiti e la loro assegnazione alle strutture.
 *
 * Questo service gestisce:
 * - Generazione e gestione dei titoli degli ospiti (hash univoco)
 * - Verifica dello stato di assegnazione degli ospiti alle strutture
 * - Aggiunta degli ospiti alle strutture
 * - Gestione dei form degli ospiti (nascondere campi non necessari)
 * - Logging delle operazioni relative agli ospiti
 * - Validazione delle operazioni di assegnazione
 */
class OspitiService {
    protected $entityTypeManager;
    protected $logger;

    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        LoggerChannelFactoryInterface $loggerFactory
    ) {
        $this->entityTypeManager = $entityTypeManager;
        $this->logger = $loggerFactory->get('fokos');
    }

    /**
     * Genera e imposta il titolo per l'entità ospite.
     */
    public function setOspiteTitle(NodeInterface $node): void {
        if ($node->bundle() !== 'ospite') {
            return;
        }

        $nome = $node->get('field_osp_nome')->value;
        $cognome = $node->get('field_osp_cognome')->value;
        $timestamp = time();
        $hash = substr(hash('sha256', $nome . $cognome . $timestamp), 0, 12);
        $node->setTitle($hash);
        
        $this->logger->notice('Generato nuovo titolo per ospite: @hash', ['@hash' => $hash]);
    }

    /**
     * Controlla se un ospite è già assegnato a una struttura.
     */
    public function isOspiteGiaAssegnato(int $ospiteId): bool {
        $query = $this->entityTypeManager->getStorage('node')->getQuery()
            ->condition('type', 'struttura')
            ->condition('field_refs_ospite', $ospiteId)
            ->accessCheck(FALSE)
            ->range(0, 1);
        
        $result = !empty($query->execute());
        
        if ($result) {
            $this->logger->notice('Ospite @id già assegnato a una struttura', ['@id' => $ospiteId]);
        }
        
        return $result;
    }

    /**
     * Aggiunge un ospite alla lista della struttura.
     */
    public function aggiungiOspiteAStruttura(int $strutturaId, int $ospiteId): void {
        $struttura = $this->entityTypeManager->getStorage('node')->load($strutturaId);
        
        if (!$struttura || $struttura->bundle() !== 'struttura') {
            $this->logger->error('Struttura non valida: @id', ['@id' => $strutturaId]);
            return;
        }

        if ($this->isOspiteGiaAssegnato($ospiteId)) {
            $this->logger->warning('Tentativo di aggiungere un ospite già assegnato: @id', ['@id' => $ospiteId]);
            return;
        }

        $ospiti_correnti = $struttura->get('field_refs_ospite')->getValue();
        $ospiti_correnti[] = ['target_id' => $ospiteId];
        $struttura->set('field_refs_ospite', $ospiti_correnti);
        $struttura->save();
        
        // Genera e salva il token di dimissione
        try {
            $ospite = $this->entityTypeManager->getStorage('node')->load($ospiteId);
            if ($ospite) {
                $token = substr(hash('sha256', $ospiteId . $strutturaId . time()), 0, 32);
                $ospite->set('field_token_dimissioni', $token);
                $ospite->save();
                
                $this->logger->notice('Token di dimissione generato per ospite @ospite', [
                    '@ospite' => $ospiteId
                ]);
            }
        } catch (\Exception $e) {
            $this->logger->error('Errore durante la generazione del token di dimissione: @error', [
                '@error' => $e->getMessage()
            ]);
        }
        
        $this->logger->notice('Ospite @ospite aggiunto alla struttura @struttura', [
            '@ospite' => $ospiteId,
            '@struttura' => $strutturaId
        ]);
    }

    /**
     * Gestisce le modifiche ai form degli ospiti.
     */
    public function alterForm(&$form, FormStateInterface $form_state, $form_id): void {
        if (in_array($form_id, ['node_ospite_form', 'node_ospite_edit_form'])) {
            $form['title']['#access'] = FALSE;
            $this->logger->debug('Nascosto campo titolo per form ospite: @form_id', ['@form_id' => $form_id]);
        }
    }
} 