<?php

declare(strict_types=1);

namespace DddForge\Scaffolding\File;

use DddForge\Scaffolding\Config\ScaffoldingConfig;
use DddForge\Scaffolding\Directory\PathCollection;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;

readonly class YamlExporter
{
    private const DIRECTORY_SEPARATOR = '/';

    public function __construct(
        private Filesystem $filesystem = new Filesystem()
    ) {
    }

    public function export(SymfonyStyle $io, PathCollection $paths, ScaffoldingConfig $config, string $filename): void
    {
        $yamlContent = $this->buildYamlStructure($paths, $config);

        try {
            $this->filesystem->dumpFile($filename, $yamlContent);
            $io->success("✓ Structure exported to: <info>$filename</info>");
        } catch (IOExceptionInterface $e) {
            $io->error("Failed to export YAML: " . $e->getMessage());
        }
    }

    private function buildYamlStructure(PathCollection $paths, ScaffoldingConfig $config): string
    {
        $type = $config->type->value;
        $yaml = "# $type: $config->name\n";
        $yaml .= "# Generated: " . date('Y-m-d H:i:s') . "\n";
        $yaml .= "# Template: " . ($config->template ?? 'custom') . "\n\n";

        $yaml .= strtolower($config->type->value) . ":\n";
        $yaml .= "  name: $config->name\n";
        $yaml .= "  baseDir: $config->baseDir\n";
        $yaml .= "  template: " . ($config->template ?? 'custom') . "\n\n";

        $yaml .= "structure:\n";

        $structure = [];
        foreach ($paths->toArray() as $path) {
            $relativePath = str_replace($config->baseDir . self::DIRECTORY_SEPARATOR . $config->name . self::DIRECTORY_SEPARATOR, '', $path->name);
            $parts = explode(self::DIRECTORY_SEPARATOR, $relativePath);

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

        foreach ($structure as $layer => $subLayers) {
            $yaml .= "  $layer:\n";
            if (empty($subLayers)) {
                $yaml .= "    subLayers: []\n";
            } else {
                $yaml .= "    subLayers:\n";
                foreach ($subLayers as $sublayer) {
                    $yaml .= "      - $sublayer\n";
                }
            }
        }

        $yaml .= "\n# Usage:\n";
        $yaml .= "# You can use this file as documentation or recreate the structure\n";
        $yaml .= "# Command: make:context $config->name";
        if ($config->template) {
            $yaml .= " --template=$config->template";
        }
        $yaml .= "\n";

        return $yaml;
    }
}
