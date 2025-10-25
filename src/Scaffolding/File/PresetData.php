<?php

declare(strict_types=1);

namespace DddForge\Scaffolding\File;

use DddForge\Scaffolding\Config\ScaffoldingType;
use DddForge\Scaffolding\Template\Layer\LayerCollection;

readonly class PresetData
{
    public function __construct(
        public string $name,
        public ScaffoldingType $type,
        public bool $withSublayers,
        public string $baseDir,
        public LayerCollection $customSublayers,
        public string $createdAt,
        public ?string $template,
    ) {
    }
}
