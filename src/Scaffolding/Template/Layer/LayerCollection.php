<?php

declare(strict_types=1);

namespace DddForge\Scaffolding\Template\Layer;

use DddForge\Support\Collection\TypedCollection;

/**
 * @extends TypedCollection<Layer>
 */
class LayerCollection extends TypedCollection
{
    protected function type(): string
    {
        return Layer::class;
    }
}
