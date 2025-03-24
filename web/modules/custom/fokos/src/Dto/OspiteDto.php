<?php

namespace Drupal\fokos\Dto;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\node\NodeInterface;

/**
 * DTO per i dati dell'ospite.
 */
class OspiteDto {
    public function __construct(
        public readonly string $nome,
        public readonly string $cognome,
        public readonly ?string $codiceFiscale,
        public readonly ?DrupalDateTime $dataNascita
    ) {}

    /**
     * Crea un DTO da un nodo ospite.
     */
    public static function fromNode(NodeInterface $node): self {
        $dataNascita = $node->get('field_osp_data_nascita')->value 
            ? new DrupalDateTime($node->get('field_osp_data_nascita')->value)
            : null;

        return new self(
            nome: $node->get('field_osp_nome')->value,
            cognome: $node->get('field_osp_cognome')->value,
            codiceFiscale: $node->get('field_osp_cf')->value,
            dataNascita: $dataNascita
        );
    }
} 