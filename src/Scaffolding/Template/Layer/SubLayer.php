<?php

declare(strict_types=1);

namespace DddForge\Scaffolding\Template\Layer;

readonly class SubLayer
{
    public function __construct(
        public string $name,
    ) {
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
        ];
    }
}
