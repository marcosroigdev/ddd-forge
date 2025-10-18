<?php

declare(strict_types=1);

namespace DddForge\Scaffolding\Directory;

use DddForge\Support\Collection\TypedCollection;

/**
 * @extends TypedCollection<Path>
 */
class PathCollection extends TypedCollection
{
    protected function type(): string
    {
        return Path::class;
    }
}
