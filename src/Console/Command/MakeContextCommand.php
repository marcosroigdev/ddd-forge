<?php

declare(strict_types=1);

namespace DddForge\Console\Command;

use DddForge\Support\Str;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use InvalidArgumentException;
use RuntimeException;

#[AsCommand(
    name: 'make:context',
    description: 'Generate a bounded context directory structure following DDD principles'
)]
final class MakeContextCommand extends Command
{
    private const NAME_ARGUMENT = 'name';
    private const DIR_OPTION = 'dir';
    private const FORCE_OPTION = 'force';
    private const DRY_RUN_OPTION = 'dry-run';
    private const WITH_SUBLAYERS_OPTION = 'with-sublayers';
    private const TEMPLATE_OPTION = 'template';
    private const INTERACTIVE_OPTION = 'interactive';
    private const SAVE_PRESET_OPTION = 'save-preset';
    private const USE_PRESET_OPTION = 'use-preset';
    private const EXPORT_OPTION = 'export';
    private const GITKEEP_OPTION = 'gitkeep';

    private const DEFAULT_BASE_DIR = 'src';
    private const DIRECTORY_SEPARATOR = '/';
    private const PRESETS_DIR = '.ddd-forge/presets';
    private const GITKEEP_FILE = '.gitkeep';

    private const LAYER_PATHS = [
        'Domain' => '/Domain',
        'Application' => '/Application',
        'Infrastructure' => '/Infrastructure',
        'UI' => '/UI',
    ];

    private const TEMPLATES = [
        'basic' => [
            'name' => 'Basic DDD (Main layers only)',
            'description' => 'Creates only the 4 main DDD layers without sublayers',
            'sublayers' => [],
        ],
        'standard' => [
            'name' => 'Standard DDD (Recommended)',
            'description' => 'Complete DDD structure with common sublayers',
            'sublayers' => [
                'Domain' => ['Model', 'Service', 'Repository', 'Event'],
                'Application' => ['Command', 'Query', 'Handler', 'Service'],
                'Infrastructure' => ['Persistence', 'Service', 'Resources'],
                'UI' => ['Controller', 'Command'],
            ],
        ],
        'cqrs' => [
            'name' => 'CQRS Pattern',
            'description' => 'Command Query Responsibility Segregation',
            'sublayers' => [
                'Domain' => ['Read', 'Write', 'Event'],
                'Application' => ['Command', 'Query', 'Handler', 'Bus'],
                'Infrastructure' => ['Read', 'Write', 'Persistence', 'Resources'],
                'UI' => ['Controller', 'Command'],
            ],
        ],
        'event-sourcing' => [
            'name' => 'Event Sourcing',
            'description' => 'Event-driven architecture with event store',
            'sublayers' => [
                'Domain' => ['Aggregate', 'Event', 'Projection'],
                'Application' => ['Command', 'Query', 'EventHandler', 'Projector'],
                'Infrastructure' => ['EventStore', 'Projection', 'Snapshot', 'Resources'],
                'UI' => ['Controller', 'Command'],
            ],
        ],
        'hexagonal' => [
            'name' => 'Hexagonal Architecture',
            'description' => 'Ports and Adapters pattern',
            'sublayers' => [
                'Domain' => ['Model', 'Port', 'Service'],
                'Application' => ['UseCase', 'Port', 'Service'],
                'Infrastructure' => ['Adapter', 'Persistence', 'External', 'Resources'],
                'UI' => ['Adapter', 'Controller', 'Command'],
            ],
        ],
    ];

    /** @var array<string, string[]> */
    private array $customSublayers = [];

    protected function configure(): void
    {
        $this
            ->addArgument(
                self::NAME_ARGUMENT,
                InputArgument::OPTIONAL,
                'The name of the bounded context to create'
            )
            ->addOption(
                self::DIR_OPTION,
                'd',
                InputOption::VALUE_OPTIONAL,
                'Target base directory where the context will be created',
                self::DEFAULT_BASE_DIR
            )
            ->addOption(
                self::FORCE_OPTION,
                'f',
                InputOption::VALUE_NONE,
                'Force creation even if directories already exist'
            )
            ->addOption(
                self::DRY_RUN_OPTION,
                null,
                InputOption::VALUE_NONE,
                'Show what would be created without actually creating directories'
            )
            ->addOption(
                self::WITH_SUBLAYERS_OPTION,
                's',
                InputOption::VALUE_NONE,
                'Create detailed sublayers within each main layer'
            )
            ->addOption(
                self::TEMPLATE_OPTION,
                't',
                InputOption::VALUE_OPTIONAL,
                'Use a predefined template: ' . implode(', ', array_keys(self::TEMPLATES))
            )
            ->addOption(
                self::INTERACTIVE_OPTION,
                'i',
                InputOption::VALUE_NONE,
                'Run in interactive mode with wizard'
            )
            ->addOption(
                self::SAVE_PRESET_OPTION,
                null,
                InputOption::VALUE_OPTIONAL,
                'Save current configuration as a reusable preset'
            )
            ->addOption(
                self::USE_PRESET_OPTION,
                'p',
                InputOption::VALUE_OPTIONAL,
                'Use a saved preset configuration'
            )
            ->addOption(
                self::EXPORT_OPTION,
                'e',
                InputOption::VALUE_OPTIONAL,
                'Export the structure to a YAML file (provide filename)'
            )
            ->addOption(
                self::GITKEEP_OPTION,
                'g',
                InputOption::VALUE_NONE,
                'Create .gitkeep files in all directories'
            )
            ->setHelp(
                'This command creates a bounded context structure following DDD principles.' . PHP_EOL . PHP_EOL .
                '<info>Interactive Mode (Recommended for first-time users):</info>' . PHP_EOL .
                '  <comment>make:context</comment>                           # Launch wizard' . PHP_EOL .
                '  <comment>make:context --interactive</comment>             # Launch wizard explicitly' . PHP_EOL . PHP_EOL .
                '<info>Quick Mode (For experienced users):</info>' . PHP_EOL .
                '  <comment>make:context UserManagement</comment>            # Basic structure only' . PHP_EOL .
                '  <comment>make:context Billing --template=cqrs</comment>   # Use CQRS template' . PHP_EOL .
                '  <comment>make:context Orders -s</comment>                 # Standard template with sublayers' . PHP_EOL . PHP_EOL .
                '<info>Presets (Save & Reuse configurations):</info>' . PHP_EOL .
                '  <comment>make:context Catalog --save-preset=ecommerce</comment>  # Save as preset' . PHP_EOL .
                '  <comment>make:context Shipping -p ecommerce</comment>            # Reuse preset' . PHP_EOL .
                '  <comment>make:context --use-preset=list</comment>                # List available presets' . PHP_EOL . PHP_EOL .
                '<info>Export & Git:</info>' . PHP_EOL .
                '  <comment>make:context Orders --export=structure.yaml</comment>   # Export to YAML' . PHP_EOL .
                '  <comment>make:context Billing -t cqrs --gitkeep</comment>        # Create .gitkeep files' . PHP_EOL . PHP_EOL .
                '<info>Available Templates:</info>' . PHP_EOL .
                $this->buildTemplateHelp()
            );
    }

    private function buildTemplateHelp(): string
    {
        $help = [];
        foreach (self::TEMPLATES as $key => $template) {
            $help[] = "  • <info>$key</info>: {$template['description']}";
        }
        return implode(PHP_EOL, $help);
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        $io = new SymfonyStyle($input, $output);

        if ($input->getOption(self::USE_PRESET_OPTION) === 'list') {
            $this->listPresets($io);
            exit(Command::SUCCESS);
        }

        if ($presetName = $input->getOption(self::USE_PRESET_OPTION)) {
            $this->loadPreset($io, $input, $presetName);
            return;
        }

        $needsWizard = !$input->getArgument(self::NAME_ARGUMENT) ||
            $input->getOption(self::INTERACTIVE_OPTION);

        if (!$needsWizard) {
            return;
        }

        $this->runWizard($io, $input);
    }

    private function runWizard(SymfonyStyle $io, InputInterface $input): void
    {
        $io->title('🏗️  DDD Context Generator Wizard');
        $io->text([
            'This wizard will guide you through creating a bounded context.',
            'Press Ctrl+C at any time to cancel.',
        ]);
        $io->newLine();

        if (!$input->getArgument(self::NAME_ARGUMENT)) {
            $name = $io->ask(
                'What is the name of your bounded context?',
                null,
                function ($answer) {
                    if (empty(trim($answer))) {
                        throw new RuntimeException('Context name cannot be empty.');
                    }
                    return $answer;
                }
            );
            $input->setArgument(self::NAME_ARGUMENT, $name);
        }

        $currentDir = $input->getOption(self::DIR_OPTION);
        if ($io->confirm("Use default directory '$currentDir'?")) {
            $io->text("  → Using directory: <info>$currentDir</info>");
        } else {
            $customDir = $io->ask('Enter custom directory path', $currentDir);
            $input->setOption(self::DIR_OPTION, $customDir);
        }

        $io->newLine();

        $templateChoices = ['custom' => 'Custom (I\'ll choose my own sublayers)'];
        foreach (self::TEMPLATES as $key => $template) {
            $templateChoices[$key] = $template['name'];
        }

        $selectedTemplate = $io->choice(
            'Choose your context architecture',
            $templateChoices,
            'standard'
        );

        if ($selectedTemplate === 'custom') {
            $io->section('Custom Sublayer Configuration');
            $this->configureCustomSublayers($io);
            $input->setOption(self::WITH_SUBLAYERS_OPTION, true);
        } elseif ($selectedTemplate !== 'basic') {
            $template = self::TEMPLATES[$selectedTemplate];
            $this->customSublayers = $template['sublayers'];
            $input->setOption(self::WITH_SUBLAYERS_OPTION, true);
            $input->setOption(self::TEMPLATE_OPTION, $selectedTemplate);

            $io->text("  ✓ Using template: <info>{$template['name']}</info>");
            $io->text("  <comment>{$template['description']}</comment>");
        }

        $io->newLine();

        if ($io->confirm('Preview structure before creating? (dry-run)')) {
            $input->setOption(self::DRY_RUN_OPTION, true);
        }

        if ($io->confirm('Save this configuration as a preset for future use?', false)) {
            $presetName = $io->ask('Enter preset name', $input->getArgument(self::NAME_ARGUMENT));
            $input->setOption(self::SAVE_PRESET_OPTION, $presetName);
        }

        if ($io->confirm('Create .gitkeep files in all directories?')) {
            $input->setOption(self::GITKEEP_OPTION, true);
        }

        $io->newLine();
    }

    private function configureCustomSublayers(SymfonyStyle $io): void
    {
        $io->text('Configure sublayers for each main layer. Leave empty to skip a layer.');
        $io->newLine();

        foreach (self::LAYER_PATHS as $layerName => $layerPath) {
            $io->section("$layerName Layer");

            $suggestions = $this->getSuggestionsForLayer($layerName);
            if (!empty($suggestions)) {
                $io->text("  <comment>Suggestions: " . implode(', ', $suggestions) . "</comment>");
            }

            $createSublayers = $io->confirm("Create sublayers for $layerName?");

            if ($createSublayers) {
                $sublayersInput = $io->ask(
                    "Enter sublayer names (comma-separated)",
                    implode(', ', $suggestions)
                );

                if ($sublayersInput) {
                    $sublayers = array_map('trim', explode(',', $sublayersInput));
                    $sublayers = array_filter($sublayers);
                    $this->customSublayers[$layerName] = $sublayers;

                    $io->text("  ✓ Will create: <info>" . implode(', ', $sublayers) . "</info>");
                }
            }

            $io->newLine();
        }
    }

    /**
     * @return string[]
     */
    private function getSuggestionsForLayer(string $layerName): array
    {
        return match($layerName) {
            'Domain' => ['Model', 'Service', 'Repository', 'Event', 'ValueObject'],
            'Application' => ['Command', 'Query', 'Handler', 'Service', 'UseCase'],
            'Infrastructure' => ['Persistence', 'Service', 'External', 'Resources'],
            'UI' => ['Controller', 'Command', 'View'],
            default => [],
        };
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $config = $this->parseInput($input);

            $paths = $this->buildDirectoryStructure(
                $config['contextName'],
                $config['baseDir'],
                $config['withSublayers'],
                $config['template']
            );

            if ($exportFile = $input->getOption(self::EXPORT_OPTION)) {
                $this->exportToYaml($io, $paths, $config, $exportFile);
            }

            if ($config['dryRun']) {
                return $this->showDryRun($io, $paths, $config);
            }

            $result = $this->createDirectories($io, $paths, $config);

            if ($result === Command::SUCCESS && $input->getOption(self::GITKEEP_OPTION)) {
                $this->createGitkeepFiles($io, $paths);
            }

            if ($result === Command::SUCCESS && $presetName = $input->getOption(self::SAVE_PRESET_OPTION)) {
                $this->savePreset($io, $presetName, $config);
            }

            return $result;

        } catch (InvalidArgumentException $e) {
            $io->error($e->getMessage());
            return Command::INVALID;
        } catch (Exception $e) {
            $io->error('An unexpected error occurred: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * @param string[] $paths
     * @param array{contextName: string, baseDir: string, force: bool, dryRun: bool, withSublayers: bool, template: ?string} $config
     */
    private function exportToYaml(SymfonyStyle $io, array $paths, array $config, string $filename): void
    {
        $filesystem = new Filesystem();

        $yamlContent = $this->buildYamlStructure($paths, $config);

        try {
            $filesystem->dumpFile($filename, $yamlContent);
            $io->success("✓ Structure exported to: <info>$filename</info>");
        } catch (IOExceptionInterface $e) {
            $io->error("Failed to export YAML: " . $e->getMessage());
        }
    }

    /**
     * @param string[] $paths
     * @param array{contextName: string, baseDir: string, force: bool, dryRun: bool, withSublayers: bool, template: ?string} $config
     */
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
                continue; // Skip root
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

    /**
     * @param string[] $paths
     */
    private function createGitkeepFiles(SymfonyStyle $io, array $paths): void
    {
        $filesystem = new Filesystem();
        $created = 0;

        $io->section('Creating .gitkeep files');

        foreach ($paths as $path) {
            $gitkeepFile = $path . '/' . self::GITKEEP_FILE;

            try {
                if (!$filesystem->exists($gitkeepFile)) {
                    $filesystem->touch($gitkeepFile);
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
     * @return array{contextName: string, baseDir: string, force: bool, dryRun: bool, withSublayers: bool, template: ?string}
     */
    private function parseInput(InputInterface $input): array
    {
        $rawName = (string) $input->getArgument(self::NAME_ARGUMENT);
        $contextName = Str::studly(trim($rawName));

        if ($contextName === '') {
            throw new InvalidArgumentException('Context name cannot be empty or contain only invalid characters.');
        }

        if (!preg_match('/^[A-Za-z][A-Za-z0-9]*$/', $contextName)) {
            throw new InvalidArgumentException('Context name must start with a letter and contain only alphanumeric characters.');
        }

        $baseDir = rtrim((string) $input->getOption(self::DIR_OPTION), self::DIRECTORY_SEPARATOR);
        $template = $input->getOption(self::TEMPLATE_OPTION);

        if ($template !== null && !isset(self::TEMPLATES[$template])) {
            throw new InvalidArgumentException(
                "Invalid template '$template'. Available templates: " .
                implode(', ', array_keys(self::TEMPLATES))
            );
        }

        return [
            'contextName' => $contextName,
            'baseDir' => $baseDir,
            'force' => (bool) $input->getOption(self::FORCE_OPTION),
            'dryRun' => (bool) $input->getOption(self::DRY_RUN_OPTION),
            'withSublayers' => (bool) $input->getOption(self::WITH_SUBLAYERS_OPTION),
            'template' => $template,
        ];
    }

    /**
     * @return string[]
     */
    private function buildDirectoryStructure(
        string $contextName,
        string $baseDir,
        bool $withSublayers = false,
        ?string $template = null
    ): array {
        $root = $baseDir . self::DIRECTORY_SEPARATOR . $contextName;
        $paths = [$root];

        foreach (self::LAYER_PATHS as $layerPath) {
            $paths[] = $root . $layerPath;
        }

        if ($withSublayers) {
            $sublayers = $this->resolveSublayers($template);

            foreach ($sublayers as $layer => $layerSublayers) {
                foreach ($layerSublayers as $sublayer) {
                    $paths[] = $root . '/' . $layer . '/' . $sublayer;
                }
            }
        }

        return $paths;
    }

    /**
     * @return array<string, string[]>
     */
    private function resolveSublayers(?string $template): array
    {
        if (!empty($this->customSublayers)) {
            return $this->customSublayers;
        }

        if ($template !== null && isset(self::TEMPLATES[$template])) {
            return self::TEMPLATES[$template]['sublayers'];
        }

        return self::TEMPLATES['standard']['sublayers'];
    }

    /**
     * @param string[] $paths
     * @param array{contextName: string, baseDir: string, force: bool, dryRun: bool, withSublayers: bool, template: ?string} $config
     */
    private function showDryRun(SymfonyStyle $io, array $paths, array $config): int
    {
        $io->title("🔍 Dry Run: {$config['contextName']} Context Structure");

        $templateInfo = $config['template']
            ? " (<info>{$config['template']}</info> template)"
            : '';

        $io->text("The following structure$templateInfo would be created:");
        $io->newLine();

        $grouped = $this->groupPathsByLayer($paths, $config['contextName']);

        foreach ($grouped as $layer => $layerPaths) {
            if ($layer === 'root') {
                $io->text("  📁 <info>{$config['contextName']}/</info>");
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

    /**
     * @param string[] $paths
     * @return array<string, string[]>
     */
    private function groupPathsByLayer(array $paths, string $contextName): array
    {
        $grouped = ['root' => []];

        foreach ($paths as $path) {
            $relativePath = str_replace($contextName . '/', '', basename(dirname($path)) . '/' . basename($path));
            $parts = explode('/', trim($relativePath, '/'));

            if (count($parts) === 1) {
                $grouped['root'][] = $path;
            } else {
                $layer = $parts[0];
                if (!isset($grouped[$layer])) {
                    $grouped[$layer] = [];
                }
                if (count($parts) > 1) {
                    $grouped[$layer][] = $path;
                }
            }
        }

        return $grouped;
    }

    /**
     * @param string[] $paths
     * @param array{contextName: string, baseDir: string, force: bool, dryRun: bool, withSublayers: bool, template: ?string} $config
     */
    private function createDirectories(SymfonyStyle $io, array $paths, array $config): int
    {
        $filesystem = new Filesystem();
        $created = 0;
        $skipped = 0;

        $io->title("🏗️  Creating {$config['contextName']} Context");

        foreach ($paths as $path) {
            try {
                if ($filesystem->exists($path) && !$config['force']) {
                    $io->text("  <comment>•</comment> <fg=yellow>Exists:</fg=yellow> $path");
                    $skipped++;
                    continue;
                }

                $filesystem->mkdir($path);
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
     * @param array{contextName: string, baseDir: string, force: bool, dryRun: bool, withSublayers: bool, template: ?string} $config
     */
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

    private function listPresets(SymfonyStyle $io): void
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

    /**
     * @param array{contextName: string, baseDir: string, force: bool, dryRun: bool, withSublayers: bool, template: ?string} $config
     */
    private function savePreset(SymfonyStyle $io, string $presetName, array $config): void
    {
        $filesystem = new Filesystem();
        $presetsDir = getcwd() . '/' . self::PRESETS_DIR;

        try {
            if (!$filesystem->exists($presetsDir)) {
                $filesystem->mkdir($presetsDir);
            }

            $presetData = [
                'name' => $presetName,
                'template' => $config['template'],
                'withSublayers' => $config['withSublayers'],
                'baseDir' => $config['baseDir'],
                'customSublayers' => $this->customSublayers,
                'createdAt' => date('Y-m-d H:i:s'),
            ];

            $jsonContent = json_encode($presetData, JSON_PRETTY_PRINT);

            if ($jsonContent === false) {
                throw new RuntimeException('Failed to encode preset data to JSON');
            }

            $presetFile = $presetsDir . '/' . $presetName . '.json';
            $filesystem->dumpFile($presetFile, $jsonContent);

            $io->success("✓ Preset '$presetName' saved successfully!");
            $io->text("  Location: <info>$presetFile</info>");
            $io->text("  Reuse with: <info>make:context NewContext --use-preset=$presetName</info>");

        } catch (IOExceptionInterface $e) {
            $io->warning("Could not save preset: " . $e->getMessage());
        } catch (RuntimeException $e) {
            $io->warning("Could not save preset: " . $e->getMessage());
        }
    }

    private function loadPreset(SymfonyStyle $io, InputInterface $input, string $presetName): void
    {
        $presetFile = getcwd() . '/' . self::PRESETS_DIR . '/' . $presetName . '.json';

        if (!file_exists($presetFile)) {
            $io->error("Preset '$presetName' not found. Use --use-preset=list to see available presets.");
            exit(Command::INVALID);
        }

        $fileContents = file_get_contents($presetFile);

        if ($fileContents === false) {
            $io->error("Could not read preset file: $presetFile");
            exit(Command::INVALID);
        }

        $presetData = json_decode($fileContents, true);

        if (!is_array($presetData)) {
            $io->error("Invalid preset file: $presetFile");
            exit(Command::INVALID);
        }

        if (!$input->getArgument(self::NAME_ARGUMENT)) {
            $io->error('Context name is required when using a preset.');
            exit(Command::INVALID);
        }

        if (isset($presetData['template'])) {
            $input->setOption(self::TEMPLATE_OPTION, $presetData['template']);
        }

        if (!empty($presetData['withSublayers'])) {
            $input->setOption(self::WITH_SUBLAYERS_OPTION, true);
        }

        if (!empty($presetData['customSublayers']) && is_array($presetData['customSublayers'])) {
            $this->customSublayers = $presetData['customSublayers'];
            $input->setOption(self::WITH_SUBLAYERS_OPTION, true);
        }

        if ($input->getOption(self::DIR_OPTION) === self::DEFAULT_BASE_DIR && !empty($presetData['baseDir'])) {
            $input->setOption(self::DIR_OPTION, $presetData['baseDir']);
        }

        $io->success("✓ Loaded preset: <info>$presetName</info>");
        $io->text("  Template: " . ($presetData['template'] ?? 'custom'));
    }
}
