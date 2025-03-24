<?php

namespace Drupal\fokos\Dto;

use Drupal\node\NodeInterface;

/**
 * DTO per i dati della struttura.
 */
class StrutturaDto {
    /**
     * @param array<int> $ospitiIds
     */
    public function __construct(
        public readonly int $id,
        public readonly array $ospitiIds
    ) {}

    /**
     * Crea un DTO da un nodo struttura.
     */
    public static function fromNode(NodeInterface $node): self {
        $ospitiIds = array_map(
            fn($item) => (int) $item['target_id'],
            $node->get('field_refs_ospite')->getValue()
        );

        return new self(
            id: (int) $node->id(),
            ospitiIds: $ospitiIds
        );
    }
} 