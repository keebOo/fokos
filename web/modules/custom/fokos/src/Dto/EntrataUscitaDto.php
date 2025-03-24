<?php

namespace Drupal\fokos\Dto;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\node\NodeInterface;

/**
 * DTO per i dati di entrata/uscita.
 */
class EntrataUscitaDto {
    public function __construct(
        public readonly int $ospiteId,
        public readonly int $strutturaId,
        public readonly DrupalDateTime $dataIn,
        public readonly ?DrupalDateTime $dataOut,
        public readonly ?int $tariffaId = null,
    ) {}

    /**
     * Crea un DTO da un nodo entrata/uscita.
     */
    public static function fromNode(NodeInterface $node): self {
        return new self(
            ospiteId: (int) $node->get('field_ref_ospite')->target_id,
            strutturaId: (int) $node->get('field_ref_struttura')->target_id,
            dataIn: new DrupalDateTime($node->get('field_eo_data_in')->value),
            dataOut: $node->get('field_eo_data_out')->isEmpty() 
                ? null 
                : new DrupalDateTime($node->get('field_eo_data_out')->value),
            tariffaId: $node->get('field_ref_tariffa')->target_id ?? null
        );
    }
} 