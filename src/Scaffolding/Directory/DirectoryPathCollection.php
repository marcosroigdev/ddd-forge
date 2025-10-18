<?php

declare(strict_types=1);

namespace DddForge\Scaffolding\Directory;

use DddForge\Scaffolding\Directory\Exception\DirectoryException;
use DddForge\Support\Collection\TypedCollection;

/**
 * @extends TypedCollection<DirectoryPath>
 */
class DirectoryPathCollection extends TypedCollection
{
    protected function type(): string
    {
        return DirectoryPath::class;
    }

    /**
     * @param array<array{name: string, path: string}> $layers
     */
    public static function fromArray(array $layers): DirectoryPathCollection
    {
        $collection = DirectoryPathCollection::createEmpty();

        foreach ($layers as $layer) {

            self::validateLayer($layer);

            $collection->add(
                new DirectoryPath(
                    $layer['name'],
                    $layer['path']
                )
            );
        }

        return $collection;
    }

    private static function validateLayer(mixed $layer): void
    {
        if (!is_array($layer)) {
            throw DirectoryException::becauseDirectoryIsNotAnArray();
        }

        if (!isset($layer['name'])) {
            throw DirectoryException::becauseDirectoryNameWasNotFoundOnDirectoryArray();
        }

        if (!is_string($layer['name'])) {
            throw DirectoryException::becauseDirectoryNameIsNotAString();
        }

        if (empty(trim($layer['name']))) {
            throw DirectoryException::becauseDirectoryNameCannotBeEmpty();
        }

        if (!isset($layer['path'])) {
            throw DirectoryException::becauseDirectoryPathWasNotFoundOnDirectoryArray();
        }

        if (!is_string($layer['path'])) {
            throw DirectoryException::becauseDirectoryPathIsNotAString();
        }

        self::validatePathFormat($layer['path']);
    }

    private static function validatePathFormat(string $path): void
    {
        if (!str_starts_with($path, '/')) {
            throw DirectoryException::becauseDirectoryPathMustStartWithSlash($path);
        }

        if (preg_match('/[<>:"|?*]/', $path)) {
            throw DirectoryException::becauseDirectoryPathContainsInvalidCharacters($path);
        }

        if (str_contains($path, '//')) {
            throw DirectoryException::becauseDirectoryPathContainsDoubleSlashes($path);
        }

        if ($path !== '/' && str_ends_with($path, '/')) {
            throw DirectoryException::becauseDirectoryPathCannotEndWithSlash($path);
        }

        $segments = explode('/', trim($path, '/'));
        foreach ($segments as $segment) {
            if ($segment !== trim($segment)) {
                throw DirectoryException::becauseDirectoryPathSegmentHasTrailingSpaces($path);
            }
        }
    }
}
