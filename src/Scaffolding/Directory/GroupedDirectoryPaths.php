<?php

declare(strict_types=1);

namespace DddForge\Scaffolding\Directory;

readonly class GroupedDirectoryPaths
{
    private const ROOT_KEY = 'root';

    /**
     * @param array<string, string[]> $groups
     */
    public function __construct(
        private array $groups,
    ) {
    }

    /**
     * @return string[]
     */
    public function getRootPaths(): array
    {
        return $this->groups[self::ROOT_KEY] ?? [];
    }

    /**
     * @return string[]
     */
    public function getPathsForLayer(string $layerName): array
    {
        return $this->groups[$layerName] ?? [];
    }

    /**
     * @return array<string, string[]>
     */
    public function toArray(): array
    {
        return $this->groups;
    }

    /**
     * @return string[]
     */
    public function getLayerNames(): array
    {
        return array_filter(array_keys($this->groups), fn ($key) => $key !== self::ROOT_KEY);
    }
}
