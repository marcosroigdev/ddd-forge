<?php

declare(strict_types=1);

namespace DddForge\Scaffolding\Template;

use DddForge\Scaffolding\Template\Layer\Layer;
use DddForge\Scaffolding\Template\Layer\LayerCollection;
use DddForge\Support\Contracts\Arrayable;

/**
 * @implements Arrayable<array{name: string, description: string, layers: array<array{name: string, subLayers: array<array<string, mixed>>}>}>
 */
readonly class Template implements Arrayable
{
    public function __construct(
        public string $name,
        public LayerCollection $layers,
        public string $description = '',
    ) {
    }

    /**
     * @return array{name: string, description: string, layers: array<array{name: string, subLayers: array<array<string, mixed>>}>}
     */
    public function toArray(): array
    {
        return [
            'name'        => $this->name,
            'description' => $this->description,
            'layers'      =>
                array_map(
                    fn (Layer $layer) => $layer->toArray(),
                    $this->layers->toArray(),
                )
        ];
    }
}
