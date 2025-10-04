<?php

declare(strict_types=1);

namespace DddForge\Scaffolding;

use DddForge\Support\Str;
use Symfony\Component\Console\Input\InputInterface;
use InvalidArgumentException;

final class InputValidator
{
    private const DIRECTORY_SEPARATOR = '/';

    public function __construct(
        private readonly TemplateEngine $templateEngine
    ) {}

    public function parseInput(InputInterface $input): array
    {
        $rawName = (string) $input->getArgument('name');
        $contextName = Str::studly(trim($rawName));

        if ($contextName === '') {
            throw new InvalidArgumentException('Context name cannot be empty or contain only invalid characters.');
        }

        if (!preg_match('/^[A-Za-z][A-Za-z0-9]*$/', $contextName)) {
            throw new InvalidArgumentException('Context name must start with a letter and contain only alphanumeric characters.');
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
            'contextName' => $contextName,
            'baseDir' => $baseDir,
            'force' => (bool) $input->getOption('force'),
            'dryRun' => (bool) $input->getOption('dry-run'),
            'withSublayers' => (bool) $input->getOption('with-sublayers'),
            'template' => $template,
        ];
    }
}
