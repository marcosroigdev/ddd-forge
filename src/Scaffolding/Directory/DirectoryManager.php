<?php

declare(strict_types=1);

namespace DddForge\Scaffolding\Directory;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;

readonly class DirectoryManager
{
    private const GITKEEP_FILE = '.gitkeep';

    public function __construct(
        private Filesystem $filesystem
    ) {}

    /**
     * @param string[] $paths
     * @param array{
     *     name: string,
     *     type: string,
     *     template: string|null,
     *     force: bool,
     *     successMessage?: string|string[],
     *     withSublayers?: bool,
     *     tipMessage?: string
     * } $config
     */
    public function createDirectories(SymfonyStyle $io, array $paths, array $config): int
    {
        $created = 0;
        $skipped = 0;

        $io->title("🏗️  Creating {$config['name']} {$config['type']}");

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

    /**
     * @param string[] $paths
     */
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

    /**
     * @param array{
     *     name: string,
     *     type: string,
     *     template: string|null,
     *     successMessage?: string|string[],
     *     withSublayers?: bool,
     *     tipMessage?: string
     * } $config
     */
    private function showSummary(SymfonyStyle $io, array $config, int $created, int $skipped): void
    {
        $templateInfo = $config['template'] ? " using {$config['template']} template" : '';
        $io->success("{$config['name']} {$config['type']} ready$templateInfo!");

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
        $io->text($config['successMessage'] ?? []);

        if (!($config['withSublayers'] ?? false)) {
            $io->newLine();
            $io->note($config['tipMessage'] ?? 'Tip: Use templates for more detailed structures.');
        }
    }
}
