<?php

declare(strict_types=1);

namespace DddForge\Scaffolding\Template;

use DddForge\Scaffolding\Template\Layer\Layer;
use DddForge\Scaffolding\Template\Layer\LayerCollection;
use DddForge\Support\Contracts\Arrayable;

readonly class Template implements Arrayable
{
    public function __construct(
        public string $name,
        public LayerCollection $layers,
        public string $description = '',
    ) {
    }

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
