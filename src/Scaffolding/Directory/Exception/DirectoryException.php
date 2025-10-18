<?php

declare(strict_types=1);

namespace DddForge\Scaffolding\Directory\Exception;

use DddForge\Exception\InvalidArgumentException;

class DirectoryException extends InvalidArgumentException
{
    public static function becauseDirectoryIsNotAnArray(): self
    {
        return new self('Invalid Directory, Directory Path has to be an array');
    }

    public static function becauseDirectoryNameWasNotFoundOnDirectoryArray(): self
    {
        return new self('Invalid Directory, Directory Name was not found on Directory array');
    }

    public static function becauseDirectoryPathWasNotFoundOnDirectoryArray(): self
    {
        return new self('Invalid Directory, Directory Path was not found on Directory array');
    }

    public static function becauseDirectoryNameIsNotAString(): self
    {
        return new self('Invalid Directory, Directory Name is not a string');
    }

    public static function becauseDirectoryNameCannotBeEmpty(): self
    {
        return new self('Invalid Directory, Directory Name cannot be empty');
    }

    public static function becauseDirectoryPathIsNotAString(): self
    {
        return new self('Invalid Directory, Directory Path is not a string');
    }

    public static function becauseDirectoryPathMustStartWithSlash(string $path): self
    {
        return new self(sprintf("Directory path must start with '/': %s", $path));
    }

    public static function becauseDirectoryPathContainsInvalidCharacters(string $path): self
    {
        return new self(sprintf("Directory path contains invalid characters: %s", $path));
    }

    public static function becauseDirectoryPathContainsDoubleSlashes(string $path): self
    {
        return new self(sprintf("Directory path cannot contain double slashes: %s", $path));
    }

    public static function becauseDirectoryPathCannotEndWithSlash(string $path): self
    {
        return new self(sprintf("Directory path cannot end with '/': %s", $path));
    }

    public static function becauseDirectoryPathSegmentHasTrailingSpaces(string $path): self
    {
        return new self(sprintf("Directory path segments cannot have trailing spaces: %s", $path));
    }
}
