<?php

declare(strict_types=1);

namespace DddForge\Tests\Console;

use DddForge\Console\Application;
use PHPUnit\Framework\TestCase;

final class CommandLoaderTest extends TestCase
{
    public function test_commands_are_registered(): void
    {
        $app = new Application();

        $this->assertTrue($app->has('make:context'));
    }
}
