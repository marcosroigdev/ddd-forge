<?php

declare(strict_types=1);

namespace DddForge\Support\Collection\Exception;

use InvalidArgumentException;

class InvalidCollectionTypeException extends InvalidArgumentException
{
    public static function becauseTypeIsInvalid(string $expectedType, string $givenType): self
    {
        return new self(
            sprintf(
                'All collection items must be of type %s, %s given',
                $expectedType,
                $givenType
            )
        );
    }
}
