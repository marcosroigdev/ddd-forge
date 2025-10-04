<?php

declare(strict_types=1);

namespace DddForge\Scaffolding;

use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

readonly class YamlExporter
{
    public function __construct(
        private Filesystem $filesystem = new Filesystem()
    ) {}

    public function export(SymfonyStyle $io, array $paths, array $config, string $filename): void
    {
        $yamlContent = $this->buildYamlStructure($paths, $config);

        try {
            $this->filesystem->dumpFile($filename, $yamlContent);
            $io->success("✓ Structure exported to: <info>$filename</info>");
        } catch (IOExceptionInterface $e) {
            $io->error("Failed to export YAML: " . $e->getMessage());
        }
    }

    private function buildYamlStructure(array $paths, array $config): string
    {
        $yaml = "# DDD Context Structure: {$config['contextName']}\n";
        $yaml .= "# Generated: " . date('Y-m-d H:i:s') . "\n";
        $yaml .= "# Template: " . ($config['template'] ?? 'custom') . "\n\n";

        $yaml .= "context:\n";
        $yaml .= "  name: {$config['contextName']}\n";
        $yaml .= "  baseDir: {$config['baseDir']}\n";
        $yaml .= "  template: " . ($config['template'] ?? 'custom') . "\n\n";

        $yaml .= "structure:\n";

        $structure = [];
        foreach ($paths as $path) {
            $relativePath = str_replace($config['baseDir'] . '/' . $config['contextName'] . '/', '', $path);
            $parts = explode('/', $relativePath);

            if (count($parts) === 1) {
                continue;
            }

            $layer = $parts[0];
            if (!isset($structure[$layer])) {
                $structure[$layer] = [];
            }

            if (count($parts) > 1) {
                $structure[$layer][] = $parts[1];
            }
        }

        foreach ($structure as $layer => $sublayers) {
            $yaml .= "  $layer:\n";
            if (empty($sublayers)) {
                $yaml .= "    sublayers: []\n";
            } else {
                $yaml .= "    sublayers:\n";
                foreach ($sublayers as $sublayer) {
                    $yaml .= "      - $sublayer\n";
                }
            }
        }

        $yaml .= "\n# Usage:\n";
        $yaml .= "# You can use this file as documentation or recreate the structure\n";
        $yaml .= "# Command: make:context {$config['contextName']}";
        if ($config['template']) {
            $yaml .= " --template={$config['template']}";
        }
        $yaml .= "\n";

        return $yaml;
    }
}
