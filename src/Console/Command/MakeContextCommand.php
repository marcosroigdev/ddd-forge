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

    private const DEFAULT_BASE_DIR = 'src';
    private const DIRECTORY_SEPARATOR = '/';

    private const LAYER_PATHS = [
        'Domain' => '/Domain',
        'Application' => '/Application',
        'Infrastructure' => '/Infrastructure',
        'UI' => '/UI',
    ];

    private const DOMAIN_SUBLAYERS = [
        '/Domain/Read',
        '/Domain/Write',
    ];

    private const APPLICATION_SUBLAYERS = [
        '/Application/Bus',
        '/Application/Query',
        '/Application/Command',
        '/Application/Listener',
        '/Application/Service',
        '/Application/Integration'
    ];

    private const INFRASTRUCTURE_SUBLAYERS = [
        '/Infrastructure/Persistence',
        '/Infrastructure/Read',
        '/Infrastructure/Write',
        '/Infrastructure/Resources'
    ];

    private const UI_SUBLAYERS = [
        '/UI/Controller',
        '/UI/Command'
    ];

    protected function configure(): void
    {
        $this
            ->addArgument(
                self::NAME_ARGUMENT,
                InputArgument::REQUIRED,
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
                'Create detailed sublayers within each main layer (Entity, UseCase, etc.)'
            )
            ->setHelp(
                'This command creates a bounded context structure following DDD principles.' . PHP_EOL .
                'By default, it creates only the main layers (Domain, Application, Infrastructure, UI).' . PHP_EOL .
                'Use --with-sublayers (-s) to create detailed subdirectories within each layer.' . PHP_EOL . PHP_EOL .
                'Examples:' . PHP_EOL .
                '  <info>make:context UserManagement</info>                    # Basic structure only' . PHP_EOL .
                '  <info>make:context UserManagement --with-sublayers</info>   # With detailed sublayers' . PHP_EOL .
                '  <info>make:context Billing -s -d src/Contexts</info>       # With sublayers in custom directory'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $config = $this->parseInput($input);
            $paths = $this->buildDirectoryStructure(
                $config['contextName'],
                $config['baseDir'],
                $config['withSublayers']
            );

            if ($config['dryRun']) {
                return $this->showDryRun($io, $paths, $config['contextName']);
            }

            return $this->createDirectories($io, $paths, $config);

        } catch (InvalidArgumentException $e) {
            $io->error($e->getMessage());
            return Command::INVALID;
        } catch (Exception $e) {
            $io->error('An unexpected error occurred: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * @return array{contextName: string, baseDir: string, force: bool, dryRun: bool, withSublayers: bool}
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

        return [
            'contextName' => $contextName,
            'baseDir' => $baseDir,
            'force' => (bool) $input->getOption(self::FORCE_OPTION),
            'dryRun' => (bool) $input->getOption(self::DRY_RUN_OPTION),
            'withSublayers' => (bool) $input->getOption(self::WITH_SUBLAYERS_OPTION),
        ];
    }

    /**
     * @return string[]
     */
    private function buildDirectoryStructure(string $contextName, string $baseDir, bool $withSublayers = false): array
    {
        $root = $baseDir . self::DIRECTORY_SEPARATOR . $contextName;
        $paths = [$root];

        foreach (self::LAYER_PATHS as $layerPath) {
            $paths[] = $root . $layerPath;
        }

        if ($withSublayers) {
            foreach (self::DOMAIN_SUBLAYERS as $sublayer) {
                $paths[] = $root . $sublayer;
            }

            foreach (self::APPLICATION_SUBLAYERS as $sublayer) {
                $paths[] = $root . $sublayer;
            }

            foreach (self::INFRASTRUCTURE_SUBLAYERS as $sublayer) {
                $paths[] = $root . $sublayer;
            }

            foreach (self::UI_SUBLAYERS as $sublayer) {
                $paths[] = $root . $sublayer;
            }
        }

        return $paths;
    }

    /**
     * @param string[] $paths
     */
    private function showDryRun(SymfonyStyle $io, array $paths, string $contextName): int
    {
        $io->title("Dry Run: $contextName Context Structure");

        $layerCount = count(self::LAYER_PATHS) + 1;
        $isBasicStructure = count($paths) === $layerCount;

        if ($isBasicStructure) {
            $io->text('The following <info>basic structure</info> would be created:');
        } else {
            $io->text('The following <info>detailed structure with sublayers</info> would be created:');
        }

        $io->newLine();

        foreach ($paths as $path) {
            $io->text("  <info>✔</info> $path");
        }

        $io->newLine();

        if ($isBasicStructure) {
            $io->note([
                'This creates only the main DDD layers.',
                'Use --with-sublayers (-s) to create detailed subdirectories within each layer.',
            ]);
        } else {
            $io->note('Run without --dry-run to actually create the directories.');
        }

        return Command::SUCCESS;
    }

    /**
     * @param string[] $paths
     * @param array{contextName: string, baseDir: string, force: bool, dryRun: bool, withSublayers: bool} $config
     */
    private function createDirectories(SymfonyStyle $io, array $paths, array $config): int
    {
        $filesystem = new Filesystem();
        $created = 0;
        $skipped = 0;

        $io->title("Creating {$config['contextName']} Context");

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
        $this->showSummary($io, $config['contextName'], $created, $skipped, $config['withSublayers']);

        return Command::SUCCESS;
    }

    /**
     * Show creation summary
     */
    private function showSummary(SymfonyStyle $io, string $contextName, int $created, int $skipped, bool $withSublayers): void
    {
        $structureType = $withSublayers ? 'detailed context structure' : 'basic context structure';
        $io->success("$contextName $structureType ready!");

        $summary = [];
        if ($created > 0) {
            $summary[] = "<info>$created directories created</info>";
        }
        if ($skipped > 0) {
            $summary[] = "<comment>$skipped directories already existed</comment>";
        }

        if (!empty($summary)) {
            $io->text('Summary: ' . implode(', ', $summary));
        }

        $io->newLine();
        $io->text([
            'Your bounded context is ready with the following structure:',
            '• <info>Domain</info>: Core business logic, entities, value objects, repositories',
            '• <info>Application</info>: Use cases, commands, queries, handlers',
            '• <info>Infrastructure</info>: External concerns, persistence, services',
            '• <info>UI</info>: User interfaces',
        ]);

        if (!$withSublayers) {
            $io->newLine();
            $io->note('Tip: Use --with-sublayers (-s) next time to create detailed subdirectories within each layer.');
        }
    }
}
