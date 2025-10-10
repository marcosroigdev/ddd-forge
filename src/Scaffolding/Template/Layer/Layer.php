<?php

declare(strict_types=1);

namespace DddForge\Scaffolding\Template\Layer;

use DddForge\Support\Contracts\Arrayable;

/**
 * @implements Arrayable<array{name: string, subLayers: array<array<string, mixed>>}>
 */
readonly class Layer implements Arrayable
{
    public function __construct(
        public string $name,
        public SubLayerCollection $subLayers,
    ) {
    }

    /**
     * @return array{name: string, subLayers: array<array<string, mixed>>}
     */
    public function toArray(): array
    {
        return [
            'name'      => $this->name,
            'subLayers' =>
                array_map(
                    fn (SubLayer $subLayer) => $subLayer->toArray(),
                    $this->subLayers->toArray(),
                )
        ];
    }
}
