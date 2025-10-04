<?php

declare(strict_types=1);

namespace DddForge\Console\Command\MakeContext;

use DddForge\Exception\AssertionFailedException;

enum ContextLayer: string
{
    case DOMAIN = 'Domain';
    case APPLICATION = 'Application';
    case INFRASTRUCTURE = 'Infrastructure';
    case UI = 'UI';

    public static function assertedFrom(string $type): self
    {
        $taskType = self::tryFrom($type);

        if (null === $taskType) {
            throw new AssertionFailedException(sprintf('<%s> is not a valid Context Layer.', $type));
        }

        return $taskType;
    }
}
