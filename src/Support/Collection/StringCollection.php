<?php

declare(strict_types=1);

namespace DddForge\Support\Collection;

/**
 * @extends TypedCollection<string>
 */
class StringCollection extends TypedCollection
{
    protected function type(): string
    {
        return 'string';
    }
}
