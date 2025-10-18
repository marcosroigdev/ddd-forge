<?php

declare(strict_types=1);

namespace DddForge\Scaffolding\Directory;

class DirectoryPathRegistry
{
    private const DEFAULT_LAYERS = [
        [
            'name' => 'Domain',
            'path' => '/Domain'
        ],
        [
            'name' => 'Application',
            'path' => '/Application'
        ],
        [
            'name' => 'Infrastructure',
            'path' => '/Infrastructure'
        ],
        [
            'name' => 'UI',
            'path' => '/UI'
        ],
    ];

    public function getDefaultPaths(): DirectoryPathCollection
    {
        return DirectoryPathCollection::fromArray(self::DEFAULT_LAYERS);
    }
}
