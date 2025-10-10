<?php

declare(strict_types=1);

namespace DddForge\Console\Command\MakeContext\Exception;

use DddForge\Support\Collection\StringCollection;
use InvalidArgumentException;

class InvalidTemplateException extends InvalidArgumentException
{
    public static function becauseTemplateIsInvalid(string $template, StringCollection $availableTemplates): self
    {
        return new self(
            sprintf(
                "Invalid template '%s'. Available templates: %s",
                $template,
                implode(', ', $availableTemplates->toArray())
            )
        );
    }
}
