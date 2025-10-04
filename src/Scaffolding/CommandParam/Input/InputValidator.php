<?php

declare(strict_types=1);

namespace DddForge\Scaffolding\CommandParam\Input;

use DddForge\Scaffolding\Template\TemplateEngine;
use DddForge\Support\Str;
use InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;

final class InputValidator
{
    private const DIRECTORY_SEPARATOR = '/';

    public function __construct(
        private readonly TemplateEngine $templateEngine
    ) {}

    public function parseInput(InputInterface $input, string $nameArgument = 'name'): array
    {
        $rawName = (string) $input->getArgument($nameArgument);
        $name = Str::studly(trim($rawName));

        if ($name === '') {
            throw new InvalidArgumentException('Name cannot be empty or contain only invalid characters.');
        }

        if (!preg_match('/^[A-Za-z][A-Za-z0-9]*$/', $name)) {
            throw new InvalidArgumentException('Name must start with a letter and contain only alphanumeric characters.');
        }

        $baseDir = rtrim((string) $input->getOption('dir'), self::DIRECTORY_SEPARATOR);
        $template = $input->getOption('template');

        if ($template !== null && !$this->templateEngine->isValidTemplate($template)) {
            throw new InvalidArgumentException(
                "Invalid template '$template'. Available templates: " .
                implode(', ', $this->templateEngine->getTemplateNames())
            );
        }

        return [
            'name' => $name,
            'baseDir' => $baseDir,
            'force' => (bool) $input->getOption('force'),
            'dryRun' => (bool) $input->getOption('dry-run'),
            'withSublayers' => (bool) $input->getOption('with-sublayers'),
            'template' => $template,
        ];
    }
}
