<?php

namespace Drupal\fokos\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Service per la gestione delle entrate e uscite degli ospiti.
 *
 * Gestisce il flusso di entrata/uscita degli ospiti nelle strutture.
 *
 * Questo service gestisce:
 * - Processamento delle nuove entrate degli ospiti nelle strutture
 * - Validazione delle date di entrata e uscita
 * - Gestione dei form di entrata/uscita (disabilitazione campi in modifica)
 * - Verifica che l'ospite non sia già assegnato ad altre strutture
 * - Coordinamento con OspitiService per l'assegnazione alle strutture
 * - Coordinamento con SoggiornoService per la creazione dei soggiorni
 * - Generazione automatica dei titoli per le entrate/uscite
 */
class EntrateUsciteService {
    protected $entityTypeManager;
    protected $logger;
    protected $ospitiService;
    protected $soggiornoService;

    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        LoggerChannelFactoryInterface $loggerFactory,
        OspitiService $ospitiService,
        SoggiornoService $soggiornoService
    ) {
        $this->entityTypeManager = $entityTypeManager;
        $this->logger = $loggerFactory->get('fokos');
        $this->ospitiService = $ospitiService;
        $this->soggiornoService = $soggiornoService;
    }

    /**
     * Gestisce le modifiche ai form di entrate/uscite.
     */
    public function alterForm(&$form, FormStateInterface $form_state, $form_id): void {
        if (in_array($form_id, ['node_entrate_uscite_form', 'node_entrate_uscite_edit_form'])) {
            // Aggiungi validazione per entrambi i form (creazione e modifica)
            $form['#validate'][] = [$this, 'validateForm'];

            // Disabilita campi in modalità modifica
            if ($form_id === 'node_entrate_uscite_edit_form') {
                if (isset($form['field_ref_struttura'])) {
                    $form['field_ref_struttura']['widget'][0]['target_id']['#disabled'] = TRUE;
                }
                if (isset($form['field_ref_ospite'])) {
                    $form['field_ref_ospite']['widget'][0]['target_id']['#disabled'] = TRUE;
                }
            }

            $this->logger->debug('Form alter applicato per @form_id', ['@form_id' => $form_id]);
        }
    }

    /**
     * Valida il form di entrata/uscita.
     */
    public function validateForm(&$form, FormStateInterface $form_state): void {
        $form_id = $form['#form_id'];
        $this->logger->debug('Validazione form per @form_id', ['@form_id' => $form_id]);

        // Validazione ospite già assegnato (solo per nuove entrate)
        if ($form_id === 'node_entrate_uscite_form') {
            $ospite_id = $form_state->getValue(['field_ref_ospite', 0, 'target_id']);
            if ($ospite_id && $this->ospitiService->isOspiteGiaAssegnato($ospite_id)) {
                $form_state->setErrorByName('field_ref_ospite', 
                    $this->t('L\'ospite selezionato è già assegnato a un\'altra struttura.')
                );
            }
        }

        // Validazione date (per entrambi i form)
        $this->validateDates($form_state);
    }

    /**
     * Valida le date di entrata e uscita.
     */
    private function validateDates(FormStateInterface $form_state): void {
        $data_in_value = $form_state->getValue(['field_eo_data_in', 0, 'value']);
        $data_out_value = $form_state->getValue(['field_eo_data_out', 0, 'value']);

        if (!$data_in_value) {
            return; // La validazione del campo required è già gestita da Drupal
        }

        try {
            $data_in = new DrupalDateTime($data_in_value);
            $today = new DrupalDateTime('today');
            
            // Normalizza le date rimuovendo l'ora
            $data_in->setTime(0, 0, 0);
            $today->setTime(0, 0, 0);

            // Verifica che la data di entrata non sia futura
            if ($data_in > $today) {
                $form_state->setErrorByName('field_eo_data_in', 
                    $this->t('La data di entrata non può essere futura.')
                );
            }

            // Verifica la data di uscita se presente
            if ($data_out_value) {
                $data_out = new DrupalDateTime($data_out_value);
                $data_out->setTime(0, 0, 0);

                if ($data_out < $data_in) {
                    $form_state->setErrorByName('field_eo_data_out', 
                        $this->t('La data di uscita non può essere precedente alla data di entrata.')
                    );
                }
            }

        } catch (\Exception $e) {
            $this->logger->error('Errore durante la validazione delle date: @error', [
                '@error' => $e->getMessage()
            ]);
            $form_state->setErrorByName('field_eo_data_in', 
                $this->t('Errore durante la validazione delle date.')
            );
        }
    }

    /**
     * Processa una nuova entrata/uscita.
     */
    public function processNewEntrata(NodeInterface $node): void {
        if ($node->bundle() !== 'entrate_uscite') {
            return;
        }

        $ospite_id = $node->get('field_ref_ospite')->target_id;
        $struttura_id = $node->get('field_ref_struttura')->target_id;
        $data_in = $node->get('field_eo_data_in')->value;
        
        if ($ospite_id && $struttura_id) {
            $this->ospitiService->aggiungiOspiteAStruttura($struttura_id, $ospite_id);

            // Se la data di entrata è oggi, crea solo il soggiorno per oggi
            if ($data_in === date('Y-m-d')) {
                $this->soggiornoService->creaSoggiornoGiornaliero($node, $data_in);
            } else {
                // Se la data è nel passato, genera tutti i soggiorni necessari
                $this->soggiornoService->generaSoggiorni($node);
            }
        }
        
        $node->setTitle("Entrata/Uscita OSP$ospite_id STR$struttura_id $data_in");
        $this->logger->notice('Processata nuova entrata/uscita: @title', ['@title' => $node->getTitle()]);
    }

    /**
     * Helper per la traduzione dei messaggi.
     */
    private function t($string, array $args = []): TranslatableMarkup {
        return new TranslatableMarkup($string, $args);
    }
} 