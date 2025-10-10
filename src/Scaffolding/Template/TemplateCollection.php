<?php

declare(strict_types=1);

namespace DddForge\Scaffolding\Template;

use DddForge\Support\Collection\StringCollection;
use DddForge\Support\Collection\TypedCollection;

class TemplateCollection extends TypedCollection
{
    protected function type(): string
    {
        return Template::class;
    }

    public function getTemplatesNames(): StringCollection
    {
        return StringCollection::create(
            array_map(
                fn (Template $template) => $template->name,
                $this->toArray(),
            )
        );
    }
}
