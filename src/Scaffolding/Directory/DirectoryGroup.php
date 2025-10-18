<?php

declare(strict_types=1);

namespace DddForge\Scaffolding\Directory;

readonly class DirectoryGroup
{
    private const ROOT_KEY = 'root';

    public function __construct(
        public string $name,
        public PathCollection $paths,
    ) {
    }

    public function isRoot(): bool
    {
        return $this->name === self::ROOT_KEY;
    }

    /**
     * @return string[]
     */
    public function paths(): array
    {
        return array_map(
            fn (Path $path) => $path->name,
            $this->paths->toArray()
        );
    }
}
