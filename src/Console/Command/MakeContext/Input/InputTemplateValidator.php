<?php

declare(strict_types=1);

namespace DddForge\Console\Command\MakeContext\Input;

use DddForge\Console\Command\MakeContext\Exception\InvalidTemplateException;
use DddForge\Scaffolding\Template\TemplateEngine;
use Symfony\Component\Console\Input\InputInterface;

readonly class InputTemplateValidator
{
    public function __construct(
        private TemplateEngine $templateEngine
    ) {
    }

    public function validate(InputInterface $input): void
    {
        $template = $input->getOption('template');

        if ($template !== null && !$this->templateEngine->isValidTemplate($template)) {
            throw InvalidTemplateException::becauseTemplateIsInvalid(
                $template,
                $this->templateEngine->getTemplateNames()
            );
        }
    }
}
