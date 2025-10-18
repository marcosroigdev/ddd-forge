<?php

declare(strict_types=1);

namespace DddForge\Scaffolding\CommandParam\Mode;

use DddForge\Scaffolding\Config\ScaffoldingConfig;
use DddForge\Scaffolding\Directory\DirectoryStructureBuilder;
use DddForge\Scaffolding\Directory\PathCollection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

final class DryRunManager
{
    public function __construct(
        private readonly DirectoryStructureBuilder $structureBuilder
    ) {
    }

    public function showDryRun(SymfonyStyle $io, PathCollection $paths, ScaffoldingConfig $config): int
    {
        $io->title("🔍 Dry Run: {$config->type} {$config->name} Structure");

        $io->text("The following structure{$config->templateInfo()} would be created:");
        $io->newLine();

        $directoryGroups = $this->structureBuilder->buildDirectoryGroups($paths, $config->name);

        foreach ($directoryGroups->toArray() as $directoryGroup) {
            if ($directoryGroup->isRoot()) {
                $io->text("  📁 <info>{$config->name}/</info>");
            } else {
                $io->text("  📂 <info>$directoryGroup->name/</info>");
                foreach ($directoryGroup->paths->toArray() as $path) {
                    $sublayer = basename($path->name);
                    $io->text("     └─ <comment>$sublayer</comment>");
                }
            }
        }

        $io->newLine();
        $io->success('Structure preview complete. Run without --dry-run to create directories.');

        return Command::SUCCESS;
    }
}
