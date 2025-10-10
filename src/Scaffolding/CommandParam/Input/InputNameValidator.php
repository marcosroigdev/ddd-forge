<?php

declare(strict_types=1);

namespace DddForge\Scaffolding\CommandParam\Input;

use DddForge\Support\Utils\Str;
use InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;

final class InputNameValidator
{
    public static function validate(InputInterface $input, string $nameArgument = 'name'): void
    {
        $name = Str::studly(trim((string) $input->getArgument($nameArgument)));

        if ($name === '') {
            throw new InvalidArgumentException('Name cannot be empty or contain only invalid characters.');
        }

        if (!preg_match('/^[A-Za-z][A-Za-z0-9]*$/', $name)) {
            throw new InvalidArgumentException('Name must start with a letter and contain only alphanumeric characters.');
        }
    }
}
