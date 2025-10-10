<?php

declare(strict_types=1);

namespace DddForge\Scaffolding\File;

use DddForge\Console\Command\MakeContext\Configuration\ContextConfigData;
use RuntimeException;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

readonly class PresetManager
{
    private const PRESETS_DIR = '.ddd-forge/presets';

    public function __construct(
        private Filesystem $filesystem = new Filesystem()
    ) {
    }

    public function save(string $name, ContextConfigData $config, array $customSubLayers): void
    {
        $presetsDir = getcwd() . '/' . self::PRESETS_DIR;

        if (!$this->filesystem->exists($presetsDir)) {
            $this->filesystem->mkdir($presetsDir);
        }

        $presetData = [
            'name' => $name,
            'template' => $config->template,
            'withSublayers' => $config->withSubLayers,
            'baseDir' => $config->baseDir,
            'customSublayers' => $customSubLayers,
            'createdAt' => date('Y-m-d H:i:s'),
        ];

        $jsonContent = json_encode($presetData, JSON_PRETTY_PRINT);

        if ($jsonContent === false) {
            throw new RuntimeException('Failed to encode preset data to JSON');
        }

        $presetFile = $presetsDir . '/' . $name . '.json';
        $this->filesystem->dumpFile($presetFile, $jsonContent);
    }

    /**
     * @return array{
     *     name: string,
     *     template: string|null,
     *     withSublayers: bool,
     *     baseDir: string,
     *     customSublayers: array<string, string[]>,
     *     createdAt: string
     * }
     */
    public function load(string $name): array
    {
        $presetFile = getcwd() . '/' . self::PRESETS_DIR . '/' . $name . '.json';

        if (!file_exists($presetFile)) {
            throw new RuntimeException("Preset '$name' not found");
        }

        $fileContents = file_get_contents($presetFile);
        if ($fileContents === false) {
            throw new RuntimeException("Could not read preset file: $presetFile");
        }

        $presetData = json_decode($fileContents, true);
        if (
            !is_array($presetData) ||
            !isset($presetData['name'], $presetData['template'], $presetData['withSublayers'], $presetData['baseDir'], $presetData['customSublayers'], $presetData['createdAt']) ||
            !is_array($presetData['customSublayers'])
        ) {
            throw new RuntimeException("Invalid preset file: $presetFile");
        }

        /** @var array{
         *     name: string,
         *     template: string|null,
         *     withSublayers: bool,
         *     baseDir: string,
         *     customSublayers: array<string, string[]>,
         *     createdAt: string
         * } $presetData
         */
        return $presetData;
    }

    public function list(SymfonyStyle $io): void
    {
        $io->title('📋 Available Presets');

        $presetsDir = getcwd() . '/' . self::PRESETS_DIR;

        if (!is_dir($presetsDir)) {
            $io->warning('No presets found. Create one using --save-preset option.');
            return;
        }

        $presets = glob($presetsDir . '/*.json');

        if (empty($presets)) {
            $io->warning('No presets found. Create one using --save-preset option.');
            return;
        }

        $tableRows = [];
        foreach ($presets as $presetFile) {
            $presetName = basename($presetFile, '.json');
            $fileContents = file_get_contents($presetFile);

            if ($fileContents === false) {
                continue;
            }

            $data = json_decode($fileContents, true);
            if (!is_array($data)) {
                continue;
            }

            $template = $data['template'] ?? 'custom';
            $sublayerCount = 0;

            if (!empty($data['customSublayers'])) {
                foreach ($data['customSublayers'] as $layers) {
                    $sublayerCount += count($layers);
                }
            }

            $fileTime = filemtime($presetFile);
            $createdDate = $fileTime !== false ? date('Y-m-d H:i', $fileTime) : 'Unknown';

            $tableRows[] = [
                $presetName,
                $template,
                $sublayerCount > 0 ? "$sublayerCount sublayers" : 'Basic',
                $createdDate
            ];
        }

        if (empty($tableRows)) {
            $io->warning('No valid presets found. Create one using --save-preset option.');
            return;
        }

        $io->table(['Name', 'Template', 'Structure', 'Created'], $tableRows);
        $io->newLine();
        $io->text('Usage: <info>make:context MyContext --use-preset=NAME</info>');
    }

    public function exists(string $name): bool
    {
        $presetFile = getcwd() . '/' . self::PRESETS_DIR . '/' . $name . '.json';
        return file_exists($presetFile);
    }
}
