<?php

declare(strict_types=1);

namespace DddForge\Scaffolding\Directory;

use DddForge\Scaffolding\Template\Layer\LayerCollection;

readonly class DirectoryBuildConfig
{
    public function __construct(
        public string $name,
        public string $baseDir,
        public DirectoryPathCollection $directoryPaths,
        public LayerCollection $customSublayers,
        public bool $withSublayers = false,
        public ?string $template = null,
    ) {
    }
}
