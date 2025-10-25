<?php

declare(strict_types=1);

namespace DddForge\Scaffolding\Config;

use DddForge\Exception\AssertionFailedException;

enum ScaffoldingType: string
{
    case CONTEXT = 'context';
    case VIEW    = 'view';

    public static function assertedFrom(string $type): self
    {
        $scaffoldingType = self::tryFrom($type);

        if (null === $scaffoldingType) {
            throw new AssertionFailedException(sprintf('<%s> is not a valid Scaffolding Type.', $type));
        }

        return $scaffoldingType;
    }
}
