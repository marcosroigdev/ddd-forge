<?php

declare(strict_types=1);

namespace DddForge\Console;

use ReflectionClass;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Application as SymfonyApplication;
use ReflectionException;
use SplFileInfo;

final class CommandLoader
{
    private const BASE_NS           = 'DddForge\\Console\\Command\\';
    private const PHP_EXTENSION_KEY = 'php';
    private const DOT = '.';

    private string $commandsDir;

    public function __construct()
    {
        $this->commandsDir = __DIR__ . '/Command';
    }

    /**
     * @throws ReflectionException
     */
    public function register(SymfonyApplication $app): void
    {
        if (!is_dir($this->commandsDir)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->commandsDir));
        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== self::PHP_EXTENSION_KEY) {
                continue;
            }

            $relativePath = $this->getRelativePath($file);
            $class        = self::BASE_NS . str_replace([DIRECTORY_SEPARATOR, self::DOT.self::PHP_EXTENSION_KEY], ['\\', ''], $relativePath);

            if (!class_exists($class)) {
                continue;
            }

            if (!is_subclass_of($class, Command::class)) {
                continue;
            }

            $rc = new ReflectionClass($class);
            if ($rc->isAbstract()) {
                continue;
            }

            $attrs = $rc->getAttributes(AsCommand::class);
            if (!$attrs) {
                continue;
            }

            $commandInstance = $rc->newInstance();
            $app->add($commandInstance);
        }
    }

    private function getRelativePath(SplFileInfo $file): string
    {
        return str_replace($this->commandsDir . DIRECTORY_SEPARATOR, '', $file->getPathname());
    }
}
