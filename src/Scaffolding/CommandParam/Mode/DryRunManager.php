<?php

declare(strict_types=1);

namespace DddForge\Scaffolding\CommandParam\Mode;

use DddForge\Scaffolding\Directory\DirectoryStructureBuilder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

final class DryRunManager
{
    public function __construct(
        private readonly DirectoryStructureBuilder $structureBuilder
    ) {}

    /**
     * @param string[] $paths
     * @param array{
     *     name: string,
     *     type: string,
     *     template: string|null
     * } $config
     */
    public function showDryRun(SymfonyStyle $io, array $paths, array $config): int
    {
        $io->title("🔍 Dry Run: {$config['name']} {$config['type']} Structure");

        $templateInfo = $config['template']
            ? " (<info>{$config['template']}</info> template)"
            : '';

        $io->text("The following structure$templateInfo would be created:");
        $io->newLine();

        $grouped = $this->structureBuilder->groupPathsByLayer($paths, $config['name']);

        foreach ($grouped as $layer => $layerPaths) {
            if ($layer === 'root') {
                $io->text("  📁 <info>{$config['name']}/</info>");
            } else {
                $io->text("  📂 <info>$layer/</info>");
                foreach ($layerPaths as $path) {
                    $sublayer = basename($path);
                    $io->text("     └─ <comment>$sublayer</comment>");
                }
            }
        }

        $io->newLine();
        $io->success('Structure preview complete. Run without --dry-run to create directories.');

        return Command::SUCCESS;
    }
}
