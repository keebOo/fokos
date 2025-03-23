<?php

namespace Drupal\fokos\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\node\NodeInterface;
use DateTimeInterface;
use DatePeriod;
use DateInterval;
use Drupal\Core\Form\FormStateInterface;

class SoggiornoService {
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
     * Crea un soggiorno giornaliero.
     */
    public function creaSoggiornoGiornaliero(NodeInterface $entrata_uscita, string $data_in): void {
        $tariffa_tid = $entrata_uscita->get('field_ref_tariffa')->target_id;
        $tariffa_value = 0;

        if ($tariffa_tid) {
            $tariffa_term = $this->entityTypeManager->getStorage('taxonomy_term')->load($tariffa_tid);
            $tariffa_value = $tariffa_term ? $tariffa_term->get('field_tariffa_importo')->value : 0;
        }

        $title = sprintf(
            "Soggiorno OSP%d STR%d %s",
            $entrata_uscita->get('field_ref_ospite')->target_id,
            $entrata_uscita->get('field_ref_struttura')->target_id,
            $data_in
        );

        $this->logger->notice('Creazione soggiorno: @title', ['@title' => $title]);

        $soggiorno = $this->entityTypeManager->getStorage('node')->create([
            'type' => 'soggiorno',
            'title' => $title,
            'field_sog_data' => $data_in,
            'field_ref_entrata_uscita' => ['target_id' => $entrata_uscita->id()],
            'field_ref_ospite' => ['target_id' => $entrata_uscita->get('field_ref_ospite')->target_id],
            'field_ref_struttura' => ['target_id' => $entrata_uscita->get('field_ref_struttura')->target_id],
            'field_sog_tariffa' => $tariffa_value,
        ]);
        
        $soggiorno->save();
    }

    /**
     * Genera soggiorni per un'entrata/uscita.
     */
    public function generaSoggiorni(NodeInterface $entrata_uscita): void {
        if ($entrata_uscita->bundle() !== 'entrate_uscite') {
            $this->logger->warning('Tentativo di generare soggiorni per un nodo non valido.');
            return;
        }

        $data_in = new DrupalDateTime($entrata_uscita->get('field_eo_data_in')->value);
        $data_in = $data_in->getPhpDateTime();

        $data_out = $entrata_uscita->get('field_eo_data_out')->isEmpty()
            ? new DrupalDateTime()
            : new DrupalDateTime($entrata_uscita->get('field_eo_data_out')->value);
        $data_out = $data_out->getPhpDateTime();

        $period = new DatePeriod(
            $data_in,
            new DateInterval('P1D'),
            $data_out,
            DatePeriod::INCLUDE_END_DATE
        );

        foreach ($period as $date) {
            $giorno = $date->format('Y-m-d');
            
            // Verifica se esiste già un soggiorno per questa data
            $query = $this->entityTypeManager->getStorage('node')->getQuery()
                ->condition('type', 'soggiorno')
                ->condition('field_ref_entrata_uscita', $entrata_uscita->id())
                ->condition('field_sog_data', $giorno)
                ->accessCheck(FALSE);
            
            $soggiorni = $query->execute();

            if (empty($soggiorni)) {
                $this->creaSoggiornoGiornaliero($entrata_uscita, $giorno);
            }
        }
    }

    /**
     * Sincronizza i soggiorni quando vengono modificate le date.
     */
    public function sincronizzaSoggiorni(NodeInterface $entrata_uscita): void {
        $original = $entrata_uscita->original ?? NULL;
        if (!$original) {
            return;
        }

        $old_data_in = new DrupalDateTime($original->get('field_eo_data_in')->value);
        $old_data_out = $original->get('field_eo_data_out')->isEmpty() 
            ? new DrupalDateTime() 
            : new DrupalDateTime($original->get('field_eo_data_out')->value);

        $new_data_in = new DrupalDateTime($entrata_uscita->get('field_eo_data_in')->value);
        $new_data_out = $entrata_uscita->get('field_eo_data_out')->isEmpty() 
            ? new DrupalDateTime() 
            : new DrupalDateTime($entrata_uscita->get('field_eo_data_out')->value);

        // Normalizza le date rimuovendo l'ora
        $old_data_in->setTime(0, 0, 0);
        $old_data_out->setTime(0, 0, 0);
        $new_data_in->setTime(0, 0, 0);
        $new_data_out->setTime(0, 0, 0);

        // Elimina soggiorni se l'intervallo si è accorciato
        if ($new_data_in > $old_data_in) {
            // Elimina i soggiorni dal vecchio inizio al giorno prima del nuovo inizio
            $this->eliminaSoggiorni(
                $entrata_uscita, 
                $old_data_in->format('Y-m-d'),
                (clone $new_data_in)->modify('-1 day')->format('Y-m-d')
            );
        }
        if ($new_data_out < $old_data_out) {
            // Elimina i soggiorni dal giorno dopo la nuova fine alla vecchia fine
            $this->eliminaSoggiorni(
                $entrata_uscita,
                (clone $new_data_out)->modify('+1 day')->format('Y-m-d'),
                $old_data_out->format('Y-m-d')
            );
        }

        // Rigenera tutti i soggiorni mancanti nell'intervallo completo
        $period = new DatePeriod(
            $new_data_in->getPhpDateTime(),
            new DateInterval('P1D'),
            $new_data_out->getPhpDateTime(),
            DatePeriod::INCLUDE_END_DATE
        );

        foreach ($period as $date) {
            $giorno = $date->format('Y-m-d');
            
            // Verifica se esiste già un soggiorno per questa data
            $query = $this->entityTypeManager->getStorage('node')->getQuery()
                ->condition('type', 'soggiorno')
                ->condition('field_ref_entrata_uscita', $entrata_uscita->id())
                ->condition('field_sog_data', $giorno)
                ->accessCheck(FALSE);
            
            $soggiorni = $query->execute();

            if (empty($soggiorni)) {
                $this->creaSoggiornoGiornaliero($entrata_uscita, $giorno);
            }
        }
    }

    /**
     * Elimina i soggiorni in un intervallo di date.
     * 
     * @param NodeInterface $entrata_uscita L'entrata/uscita di riferimento
     * @param string $start_date Data di inizio dell'intervallo (inclusa)
     * @param string $end_date Data di fine dell'intervallo (inclusa)
     */
    private function eliminaSoggiorni(NodeInterface $entrata_uscita, string $start_date, string $end_date): void {
        $query = $this->entityTypeManager->getStorage('node')->getQuery()
            ->condition('type', 'soggiorno')
            ->condition('field_ref_entrata_uscita', $entrata_uscita->id())
            ->condition('field_sog_data', [$start_date, $end_date], 'BETWEEN')
            ->accessCheck(FALSE);
        
        $soggiorni_ids = $query->execute();
        
        if (!empty($soggiorni_ids)) {
            $soggiorni = $this->entityTypeManager->getStorage('node')->loadMultiple($soggiorni_ids);
            foreach ($soggiorni as $soggiorno) {
                $soggiorno->delete();
                $this->logger->notice('Eliminato soggiorno: @id', ['@id' => $soggiorno->id()]);
            }
        }
    }

    /**
     * Gestisce le modifiche ai form dei soggiorni.
     */
    public function alterForm(&$form, FormStateInterface $form_state, $form_id): void {
        if (in_array($form_id, ['node_soggiorno_form', 'node_soggiorno_edit_form'])) {
            $form['title']['#access'] = FALSE;
            $this->logger->debug('Nascosto campo titolo per form soggiorno: @form_id', ['@form_id' => $form_id]);
        }
    }

    /**
     * Elimina tutti i soggiorni associati a un'entrata/uscita.
     * 
     * @param NodeInterface $entrata_uscita L'entrata/uscita di cui eliminare i soggiorni
     * @return int Il numero di soggiorni eliminati
     */
    public function eliminaSoggiorniPerEntrataUscita(NodeInterface $entrata_uscita): int {
        if ($entrata_uscita->bundle() !== 'entrate_uscite') {
            $this->logger->warning('Tentativo di eliminare soggiorni per un nodo non valido.');
            return 0;
        }

        $query = $this->entityTypeManager->getStorage('node')->getQuery()
            ->condition('type', 'soggiorno')
            ->condition('field_ref_entrata_uscita', $entrata_uscita->id())
            ->accessCheck(FALSE);
        
        $soggiorni_ids = $query->execute();
        
        if (!empty($soggiorni_ids)) {
            $soggiorni = $this->entityTypeManager->getStorage('node')->loadMultiple($soggiorni_ids);
            foreach ($soggiorni as $soggiorno) {
                $soggiorno->delete();
                $this->logger->notice('Eliminato soggiorno: @id', ['@id' => $soggiorno->id()]);
            }
        }

        return count($soggiorni_ids);
    }
} 