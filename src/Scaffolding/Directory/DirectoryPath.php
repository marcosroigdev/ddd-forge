<?php

declare(strict_types=1);

namespace DddForge\Scaffolding\Directory;

readonly class DirectoryPath
{
    public function __construct(
        public string $name,
        public string $path,
    ) {
    }
}
