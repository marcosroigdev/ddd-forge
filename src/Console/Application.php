<?php

declare(strict_types=1);

namespace DddForge\Console;

use DddForge\App\AppMeta;
use Symfony\Component\Console\Application as SymfonyApplication;
use ReflectionException;

final class Application extends SymfonyApplication
{
    /**
     * @throws ReflectionException
     */
    public function __construct()
    {
        parent::__construct(AppMeta::name(), AppMeta::version());
        (new CommandLoader())->register($this);
    }
}
