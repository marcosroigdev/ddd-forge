<?php

declare(strict_types=1);

namespace DddForge\Scaffolding\Directory;

use DddForge\Support\Collection\TypedCollection;

/**
 * @extends TypedCollection<DirectoryGroup>
 */
class DirectoryGroupCollection extends TypedCollection
{
    protected function type(): string
    {
        return DirectoryGroup::class;
    }

    public function findByName(string $name): ?DirectoryGroup
    {
        foreach ($this->items as $directoryGroup) {
            if ($directoryGroup->name === $name) {
                return $directoryGroup;
            }
        }

        return null;
    }
}
