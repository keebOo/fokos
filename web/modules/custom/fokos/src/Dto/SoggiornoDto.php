<?php

namespace Drupal\fokos\Dto;

use Drupal\node\NodeInterface;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * DTO per i dati del soggiorno.
 */
class SoggiornoDto {
    public function __construct(
        public readonly int $ospiteId,
        public readonly int $strutturaId,
        public readonly DrupalDateTime $data,
        public readonly float $tariffa,
        public readonly int $entrataUscitaId
    ) {}

    /**
     * Crea un DTO da un nodo soggiorno.
     */
    public static function fromNode(NodeInterface $node): self {
        return new self(
            ospiteId: (int) $node->get('field_ref_ospite')->target_id,
            strutturaId: (int) $node->get('field_ref_struttura')->target_id,
            data: new DrupalDateTime($node->get('field_sog_data')->value),
            tariffa: (float) $node->get('field_sog_tariffa')->value,
            entrataUscitaId: (int) $node->get('field_ref_entrata_uscita')->target_id
        );
    }
} 