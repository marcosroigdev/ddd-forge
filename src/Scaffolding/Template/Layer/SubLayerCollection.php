<?php

declare(strict_types=1);

namespace DddForge\Scaffolding\Template\Layer;

use DddForge\Support\Collection\TypedCollection;

class SubLayerCollection extends TypedCollection
{
    protected function type(): string
    {
        return SubLayer::class;
    }
}
