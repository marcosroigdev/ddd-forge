<?php

declare(strict_types=1);

namespace DddForge\Scaffolding\Template\Layer\Exception;

use InvalidArgumentException;

class LayerException extends InvalidArgumentException
{
    public static function becauseLayerNameIsInvalid(mixed $name): self
    {
        return new self(
            sprintf(
                'Invalid Layer name %s, Layer name has to be a string',
                $name
            )
        );
    }

    public static function becauseLayerNameCannotBeEmpty(): self
    {
        return new self(
            'Invalid Layer name: %s, Layer name cannot be empty',
        );
    }
}
