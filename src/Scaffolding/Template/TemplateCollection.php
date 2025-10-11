<?php

declare(strict_types=1);

namespace DddForge\Scaffolding\Template;

use DddForge\Scaffolding\Template\Exception\TemplateException;
use DddForge\Support\Collection\StringCollection;
use DddForge\Support\Collection\TypedCollection;

/**
 * @extends TypedCollection<Template>
 */
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

    public function findByNameOrFail(string $name): Template
    {
        foreach ($this->items as $template) {
            if($template->name === $name) {
                return $template;
            }
        }

        throw TemplateException::becauseTemplateWasNotFoundWithName($name);
    }
}
