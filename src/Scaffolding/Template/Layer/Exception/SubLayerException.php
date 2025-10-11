<?php

declare(strict_types=1);

namespace DddForge\Scaffolding\Template\Layer\Exception;

use InvalidArgumentException;

class SubLayerException extends InvalidArgumentException
{
    public static function becauseSubLayersIsNotArray(): self
    {
        return new self(
            'Invalid SubLayers, SubLayers has to be a valid array',
        );
    }

    public static function becauseSubLayerNameIsInvalid(mixed $name): self
    {
        return new self(
            sprintf(
                'Invalid SubLayer name %s, SubLayer name has to be a string',
                $name
            )
        );
    }

    public static function becauseSubLayerNameCannotBeEmpty(): self
    {
        return new self(
            'Invalid SubLayer name: %s, SubLayer name cannot be empty',
        );
    }
}
