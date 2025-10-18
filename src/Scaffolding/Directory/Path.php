<?php

declare(strict_types=1);

namespace DddForge\Scaffolding\Directory;

readonly class Path
{
    public function __construct(
        public string $name
    ) {
    }
}
