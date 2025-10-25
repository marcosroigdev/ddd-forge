<?php

declare(strict_types=1);

namespace DddForge\Scaffolding\Directory;

use DddForge\Scaffolding\Config\ScaffoldingConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;

readonly class DirectoryManager
{
    private const DIRECTORY_SEPARATOR = '/';
    private const GIT_KEEP_FILE = '.gitkeep';

    public function __construct(
        private Filesystem $filesystem
    ) {
    }

    public function createDirectories(SymfonyStyle $io, PathCollection $paths, ScaffoldingConfig $config): int
    {
        $created = 0;
        $skipped = 0;

        $io->title("🏗️  Creating $config->name {$config->type->value}");

        foreach ($paths->toArray() as $path) {
            try {
                if ($this->filesystem->exists($path->name) && !$config->force) {
                    $io->text("  <comment>•</comment> <fg=yellow>Exists:</fg=yellow> $path->name");
                    $skipped++;
                    continue;
                }

                $this->filesystem->mkdir($path->name);
                $io->text("  <info>✔</info> <fg=green>Created:</fg=green> $path->name");
                $created++;

            } catch (IOExceptionInterface $e) {
                $io->error("Failed to create directory: $path->name. Error: " . $e->getMessage());
                return Command::FAILURE;
            }
        }

        $io->newLine();
        $this->showSummary($io, $config, $created, $skipped);

        return Command::SUCCESS;
    }

    public function createGitKeepFiles(SymfonyStyle $io, PathCollection $paths): void
    {
        $created = 0;

        $io->section('Creating .gitkeep files');

        foreach ($paths->toArray() as $path) {
            $gitKeepFile = $path->name . self::DIRECTORY_SEPARATOR . self::GIT_KEEP_FILE;

            try {
                if (!$this->filesystem->exists($gitKeepFile)) {
                    $this->filesystem->touch($gitKeepFile);
                    $io->text("  <info>✔</info> Created: $gitKeepFile");
                    $created++;
                }
            } catch (IOExceptionInterface) {
                $io->warning("Could not create .gitkeep in: $path->name");
            }
        }

        $io->text("\n<info>✓ Created $created .gitkeep files</info>");
        $io->text('  These files ensure empty directories are tracked by Git.');
    }

    private function showSummary(SymfonyStyle $io, ScaffoldingConfig $config, int $created, int $skipped): void
    {
        $io->success("$config->name {$config->type->value} ready{$config->templateText()}!");

        $summary = [];
        if ($created > 0) {
            $summary[] = "<info>$created directories created</info>";
        }
        if ($skipped > 0) {
            $summary[] = "<comment>$skipped directories already existed</comment>";
        }

        if (!empty($summary)) {
            $io->text('📊 Summary: ' . implode(', ', $summary));
        }

        $io->newLine();
        $io->text($config->successMessages);

        if (!$config->withSubLayers && $config->tipMessage) {
            $io->newLine();
            $io->note($config->tipMessage);
        }
    }
}
