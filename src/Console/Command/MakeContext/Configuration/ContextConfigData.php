<?php

declare(strict_types=1);

namespace DddForge\Console\Command\MakeContext\Configuration;

readonly class ContextConfigData
{
    public function __construct(
        public string $name,
        public string $baseDir,
        public bool $force,
        public bool $dryRun,
        public bool $withSubLayers,
        public ?string $template = null
    ) {
    }
}
