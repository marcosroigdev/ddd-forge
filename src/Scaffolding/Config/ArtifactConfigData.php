<?php

declare(strict_types=1);

namespace DddForge\Scaffolding\Config;

readonly class ArtifactConfigData
{
    public function __construct(
        public ScaffoldingType $type,
        public string $name,
        public string $baseDir,
        public bool $force,
        public bool $dryRun,
        public bool $withSubLayers,
        public ?string $template = null
    ) {}

    public function templateExists(): bool
    {
        return !is_null($this->template);
    }
}
