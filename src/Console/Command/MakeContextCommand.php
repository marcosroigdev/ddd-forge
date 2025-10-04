<?php

declare(strict_types=1);

namespace DddForge\Console\Command;

use DddForge\Scaffolding\DirectoryManager;
use DddForge\Scaffolding\DirectoryStructureBuilder;
use DddForge\Scaffolding\DryRunManager;
use DddForge\Scaffolding\InputValidator;
use DddForge\Scaffolding\InteractiveWizard;
use DddForge\Scaffolding\PresetManager;
use DddForge\Scaffolding\TemplateEngine;
use DddForge\Scaffolding\YamlExporter;
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
    private array $customSublayers = [];

    public function __construct(
        private PresetManager $presetManager,
        private DirectoryStructureBuilder $structureBuilder,
        private InteractiveWizard $wizard,
        private YamlExporter $yamlExporter,
        private DirectoryManager $directoryManager,
        private InputValidator $validator,
        private TemplateEngine $templateEngine,
        private DryRunManager $dryRunManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::OPTIONAL, 'The name of the bounded context to create')
            ->addOption('dir', 'd', InputOption::VALUE_OPTIONAL, 'Target base directory where the context will be created', 'src')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force creation even if directories already exist')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show what would be created without actually creating directories')
            ->addOption('with-sublayers', 's', InputOption::VALUE_NONE, 'Create detailed sublayers within each main layer')
            ->addOption('template', 't', InputOption::VALUE_OPTIONAL, 'Use a predefined template: ' . implode(', ', $this->templateEngine->getTemplateNames()))
            ->addOption('interactive', 'i', InputOption::VALUE_NONE, 'Run in interactive mode with wizard')
            ->addOption('save-preset', null, InputOption::VALUE_OPTIONAL, 'Save current configuration as a reusable preset')
            ->addOption('use-preset', 'p', InputOption::VALUE_OPTIONAL, 'Use a saved preset configuration')
            ->addOption('export', 'e', InputOption::VALUE_OPTIONAL, 'Export the structure to a YAML file (provide filename)')
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

        $wizardResult = $this->wizard->run($io, $input);
        $this->customSublayers = $wizardResult['customSublayers'];
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $config = $this->validator->parseInput($input);

            $paths = $this->structureBuilder->build(
                $config['contextName'],
                $config['baseDir'],
                $config['withSublayers'],
                $config['template'],
                $this->customSublayers
            );

            if ($exportFile = $input->getOption('export')) {
                $this->yamlExporter->export($io, $paths, $config, $exportFile);
            }

            if ($config['dryRun']) {
                return $this->dryRunManager->showDryRun($io, $paths, $config);
            }

            $result = $this->directoryManager->createDirectories($io, $paths, $config);

            if ($result === Command::SUCCESS && $input->getOption('gitkeep')) {
                $this->directoryManager->createGitkeepFiles($io, $paths);
            }

            if ($result === Command::SUCCESS && $presetName = $input->getOption('save-preset')) {
                $this->presetManager->save($presetName, $config, $this->customSublayers);
                $io->success("✓ Preset '$presetName' saved successfully!");
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

    private function loadPreset(SymfonyStyle $io, InputInterface $input, string $presetName): void
    {
        try {
            $presetData = $this->presetManager->load($presetName);

            if (!$input->getArgument('name')) {
                $io->error('Context name is required when using a preset.');
                exit(Command::INVALID);
            }

            if (isset($presetData['template'])) {
                $input->setOption('template', $presetData['template']);
            }

            if (!empty($presetData['withSublayers'])) {
                $input->setOption('with-sublayers', true);
            }

            if (!empty($presetData['customSublayers']) && is_array($presetData['customSublayers'])) {
                $this->customSublayers = $presetData['customSublayers'];
                $input->setOption('with-sublayers', true);
            }

            if ($input->getOption('dir') === 'src' && !empty($presetData['baseDir'])) {
                $input->setOption('dir', $presetData['baseDir']);
            }

            $io->success("✓ Loaded preset: <info>$presetName</info>");
            $io->text("  Template: " . ($presetData['template'] ?? 'custom'));

        } catch (Exception $e) {
            $io->error($e->getMessage());
            exit(Command::INVALID);
        }
    }
}
