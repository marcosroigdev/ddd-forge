<?php

declare(strict_types=1);

namespace DddForge\Scaffolding\Template\Exception;

use RuntimeException;

class TemplateException extends RuntimeException
{
    public static function becauseTemplateWasNotFoundWithName(string $name): self
    {
        return new self(
            sprintf(
                'Template with name %s was not found',
                $name
            )
        );
    }
}
