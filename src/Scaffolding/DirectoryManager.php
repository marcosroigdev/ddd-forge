<?php

declare(strict_types=1);

namespace DddForge\Scaffolding;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

final class DirectoryManager
{
    private const GITKEEP_FILE = '.gitkeep';

    public function __construct(
        private readonly Filesystem $filesystem = new Filesystem()
    ) {}

    public function createDirectories(SymfonyStyle $io, array $paths, array $config): int
    {
        $created = 0;
        $skipped = 0;

        $io->title("🏗️  Creating {$config['contextName']} Context");

        foreach ($paths as $path) {
            try {
                if ($this->filesystem->exists($path) && !$config['force']) {
                    $io->text("  <comment>•</comment> <fg=yellow>Exists:</fg=yellow> $path");
                    $skipped++;
                    continue;
                }

                $this->filesystem->mkdir($path);
                $io->text("  <info>✔</info> <fg=green>Created:</fg=green> $path");
                $created++;

            } catch (IOExceptionInterface $e) {
                $io->error("Failed to create directory: $path. Error: " . $e->getMessage());
                return Command::FAILURE;
            }
        }

        $io->newLine();
        $this->showSummary($io, $config, $created, $skipped);

        return Command::SUCCESS;
    }

    public function createGitkeepFiles(SymfonyStyle $io, array $paths): void
    {
        $created = 0;

        $io->section('Creating .gitkeep files');

        foreach ($paths as $path) {
            $gitkeepFile = $path . '/' . self::GITKEEP_FILE;

            try {
                if (!$this->filesystem->exists($gitkeepFile)) {
                    $this->filesystem->touch($gitkeepFile);
                    $io->text("  <info>✔</info> Created: $gitkeepFile");
                    $created++;
                }
            } catch (IOExceptionInterface) {
                $io->warning("Could not create .gitkeep in: $path");
            }
        }

        $io->text("\n<info>✓ Created $created .gitkeep files</info>");
        $io->text('  These files ensure empty directories are tracked by Git.');
    }

    private function showSummary(SymfonyStyle $io, array $config, int $created, int $skipped): void
    {
        $templateInfo = $config['template'] ? " using {$config['template']} template" : '';
        $io->success("{$config['contextName']} context ready$templateInfo!");

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
        $io->text([
            '🎯 Your bounded context is ready with the following structure:',
            '   • <info>Domain</info>: Core business logic, entities, value objects, repositories',
            '   • <info>Application</info>: Use cases, commands, queries, handlers',
            '   • <info>Infrastructure</info>: External concerns, persistence, services',
            '   • <info>UI</info>: User interfaces',
        ]);

        $io->newLine();
        $io->text([
            '💡 <comment>Next steps:</comment>',
            '   1. Start creating your domain entities in the Domain layer',
            '   2. Define your use cases in the Application layer',
            '   3. Implement infrastructure adapters as needed',
        ]);

        if (!$config['withSublayers']) {
            $io->newLine();
            $io->note('Tip: Use --template=standard or --interactive next time for a more detailed structure.');
        }
    }
}
