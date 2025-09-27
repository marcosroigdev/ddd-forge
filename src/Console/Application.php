<?php

declare(strict_types=1);

namespace DddForge\Console;

use DddForge\Console\Command\InitCommand;
use Symfony\Component\Console\Application as SymfonyApplication;

final class Application extends SymfonyApplication
{
    public function __construct()
    {
        parent::__construct('DDD-Forge', '0.1.0');
        $this->add(new InitCommand());
    }
}
