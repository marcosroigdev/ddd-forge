<?php

declare(strict_types=1);

namespace DddForge\Console\Command;

use DddForge\Config\ForgePaths;
use DddForge\Console\Command\MakeContext\Input\InputTemplateValidator;
use DddForge\Scaffolding\CommandParam\Input\InputNameValidator;
use DddForge\Scaffolding\CommandParam\Mode\DryRunManager;
use DddForge\Scaffolding\CommandParam\Mode\InteractiveWizard;
use DddForge\Scaffolding\Config\ArtifactConfigData;
use DddForge\Scaffolding\Config\ScaffoldingConfig;
use DddForge\Scaffolding\Config\ScaffoldingType;
use DddForge\Scaffolding\Directory\DirectoryBuildConfig;
use DddForge\Scaffolding\Directory\DirectoryManager;
use DddForge\Scaffolding\Directory\DirectoryPathCollection;
use DddForge\Scaffolding\Directory\DirectoryStructureBuilder;
use DddForge\Scaffolding\File\PresetManager;
use DddForge\Scaffolding\File\YamlExporter;
use DddForge\Scaffolding\Template\Layer\LayerCollection;
use DddForge\Scaffolding\Template\TemplateEngine;
use DddForge\Support\Utils\Str;
use Exception;
use InvalidArgumentException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name       : 'make:context',
    description: 'Generate a bounded context directory structure following DDD principles'
)]
final class MakeContextCommand extends Command
{
    private const DIRECTORY_SEPARATOR = '/';

    private LayerCollection $customSublayers;

    public function __construct(
        private readonly PresetManager $presetManager,
        private readonly DirectoryStructureBuilder $directoryStructureBuilder,
        private readonly InteractiveWizard $wizard,
        private readonly YamlExporter $yamlExporter,
        private readonly DirectoryManager $directoryManager,
        private readonly InputTemplateValidator $inputTemplateValidator,
        private readonly TemplateEngine $templateEngine,
        private readonly DryRunManager $dryRunManager
    ) {
        $this->customSublayers = LayerCollection::createEmpty();
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::OPTIONAL, 'The name of the bounded context to create')
            ->addOption(
                'dir',
                'd',
                InputOption::VALUE_OPTIONAL,
                'Target base directory where the context will be created',
                'src'
            )
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force creation even if directories already exist')
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Show what would be created without actually creating directories'
            )
            ->addOption(
                'with-sublayers',
                's',
                InputOption::VALUE_NONE,
                'Create detailed sublayers within each main layer'
            )
            ->addOption(
                'template',
                't',
                InputOption::VALUE_OPTIONAL,
                sprintf(
                    'Use a predefined template: %s',
                    implode(', ', $this->templateEngine->getTemplateNames()->toArray())
                )
            )
            ->addOption('interactive', 'i', InputOption::VALUE_NONE, 'Run in interactive mode with wizard')
            ->addOption(
                'save-preset',
                null,
                InputOption::VALUE_OPTIONAL,
                'Save current configuration as a reusable preset'
            )
            ->addOption('use-preset', 'p', InputOption::VALUE_OPTIONAL, 'Use a saved preset configuration')
            ->addOption(
                'export',
                'e',
                InputOption::VALUE_OPTIONAL,
                'Export the structure to a YAML file (provide filename)'
            )
            ->addOption('gitkeep', 'g', InputOption::VALUE_NONE, 'Create .gitkeep files in all directories')
            ->setHelp($this->buildHelp());
    }

    private function buildHelp(): string
    {
        return 'This command creates a bounded context structure following DDD principles.' . PHP_EOL . PHP_EOL .
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
            $this->templateEngine->buildTemplateHelp();
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        $io = new SymfonyStyle($input, $output);

        if ($input->getOption('use-preset') === 'list') {
            $this->presetManager->list($io);
            exit(Command::SUCCESS);
        }

        if ($presetName = $input->getOption('use-preset')) {
            $this->loadPreset($io, $input, $presetName);
            return;
        }

        $needsWizard = !$input->getArgument('name') || $input->getOption('interactive');

        if (!$needsWizard) {
            return;
        }

        $wizardConfig = [
            'title'        => 'Context Generator Wizard',
            'description'  => [
                'Welcome to the DDD Context Generator!',
                'This wizard will help you create a bounded context structure.',
                'Answer a few questions to customize your context architecture.'
            ],
            'nameArgument' => 'name',
            'namePrompt'   => 'What is the name of your bounded context?'
        ];

        $this->customSublayers = $this->wizard->run($io, $input, $wizardConfig);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $this->validateInput($input);

            $config = $this->getConfigContextData($input);

            $directoryPaths = $this->directoryStructureBuilder->build(
                $this->getDirectoryBuildConfig(
                    $config,
                    $this->directoryStructureBuilder->getDefaultDirectoryPaths()
                )
            );

            $scaffoldingConfig = ScaffoldingConfig::forContext(
                $config->name,
                $config->baseDir,
                $config->force,
                $config->withSubLayers,
                $config->template,
            );

            if ($config->dryRun) {
                return $this->dryRunManager->showDryRun($io, $directoryPaths, $scaffoldingConfig);
            }

            $exportFile = $input->getOption('export');

            if ($exportFile) {
                $exportPath = getcwd() . ForgePaths::structure() . ltrim($exportFile, '/');
                $this->yamlExporter->export($io, $directoryPaths, $scaffoldingConfig, $exportPath);
            }

            $directoriesCreationResult = $this->directoryManager->createDirectories($io, $directoryPaths, $scaffoldingConfig);

            $gitKeep = $input->getOption('gitkeep');

            if ($this->wasDirectoriesCreatedSuccessfully($directoriesCreationResult) && $gitKeep) {
                $this->directoryManager->createGitKeepFiles($io, $directoryPaths);
            }

            $presetName = $input->getOption('save-preset');

            if ($this->wasDirectoriesCreatedSuccessfully($directoriesCreationResult) && $presetName) {
                $this->presetManager->save($presetName, $config, $this->customSublayers);
                $io->success("✓ Preset '$presetName' saved successfully!");
            }

            return $directoriesCreationResult;

        } catch (InvalidArgumentException $e) {
            $io->error($e->getMessage());
            return Command::INVALID;
        } catch (Exception $e) {
            $io->error('An unexpected error occurred: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function validateInput(InputInterface $input): void
    {
        InputNameValidator::validate($input);
        $this->inputTemplateValidator->validate($input);
    }

    private function loadPreset(SymfonyStyle $io, InputInterface $input, string $presetName): void
    {
        try {
            $presetData = $this->presetManager->load($presetName);

            if ($presetData->type !== ScaffoldingType::CONTEXT) {
                $io->error("Preset '$presetName' is not a context preset (type: {$presetData->type->value})");
                exit(Command::INVALID);
            }

            if (!$input->getArgument('name')) {
                $io->error('Context name is required when using a preset.');
                exit(Command::INVALID);
            }

            if (isset($presetData->template)) {
                $input->setOption('template', $presetData->template);
            }

            if (!empty($presetData->withSublayers)) {
                $input->setOption('with-sublayers', true);
            }

            if (!$presetData->customSublayers->isEmpty()) {
                $this->customSublayers = $presetData->customSublayers;
                $input->setOption('with-sublayers', true);
            }

            if ($input->getOption('dir') === 'src' && !empty($presetData->baseDir)) {
                $input->setOption('dir', $presetData->baseDir);
            }

            $io->success("✓ Loaded preset: <info>$presetName</info>");
            $io->text("  Template: " . ($presetData->template ?? 'custom'));

        } catch (Exception $e) {
            $io->error($e->getMessage());
            exit(Command::INVALID);
        }
    }

    private function getConfigContextData(InputInterface $input): ArtifactConfigData
    {
        return new ArtifactConfigData(
            ScaffoldingType::CONTEXT,
            Str::studly(trim((string) $input->getArgument('name'))),
            rtrim((string) $input->getOption('dir'), self::DIRECTORY_SEPARATOR),
            (bool) $input->getOption('force'),
            (bool) $input->getOption('dry-run'),
            (bool) $input->getOption('with-sublayers'),
            $input->getOption('template')
        );
    }

    private function getDirectoryBuildConfig(
        ArtifactConfigData $config,
        DirectoryPathCollection $directoryPaths
    ): DirectoryBuildConfig {
        return new DirectoryBuildConfig(
            $config->name,
            $config->baseDir,
            $directoryPaths,
            $this->customSublayers,
            $config->withSubLayers,
            $config->template,
        );
    }

    private function wasDirectoriesCreatedSuccessfully(int $directoriesCreationResult): bool
    {
        return $directoriesCreationResult === Command::SUCCESS;
    }
}
