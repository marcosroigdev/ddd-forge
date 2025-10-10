<?php

declare(strict_types=1);

namespace DddForge\Scaffolding\Template;

use DddForge\Support\Collection\TypedCollection;

class TemplateCollection extends TypedCollection
{
    protected function type(): string
    {
        return Template::class;
    }
}
