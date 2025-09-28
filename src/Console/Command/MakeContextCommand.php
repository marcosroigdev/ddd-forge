<?php
declare(strict_types=1);

namespace DddForge\Console\Command;

use DddForge\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(name: 'make:context', description: 'Generate a bounded context directory structure')]
final class MakeContextCommand extends Command
{
    private const                         CONTEXT_NAME_INPUT_DESCRIPTION = 'Context name';
    private const                         DIRECTORY_INPUT_DESCRIPTION    = 'Target base directory';
    private const                         FORCE_INPUT_DESCRIPTION        = 'Overwrite if needed';
    private const                         NAME_INPUT                     = 'name';
    private const                         DIR_INPUT                      = 'dir';
    private const                         FORCE_INPUT                    = 'force';
    private const                         SLASH_CHAR                     = '/';
    private const                         DOMAIN_PATH                    = '/Domain';
    private const                         APPLICATION_PATH               = '/Application';
    private const                         INFRASTRUCTURE_PATH            = '/Infrastructure';
    private const                         UI_PATH                        = '/UI';

    protected function configure(): void
    {
        $this
            ->addArgument(self::NAME_INPUT, InputArgument::REQUIRED, self::CONTEXT_NAME_INPUT_DESCRIPTION)
            ->addOption(self::DIR_INPUT, null, InputOption::VALUE_OPTIONAL, self::DIRECTORY_INPUT_DESCRIPTION, 'src')
            ->addOption(self::FORCE_INPUT, 'f', InputOption::VALUE_NONE, self::FORCE_INPUT_DESCRIPTION);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $raw     = (string) $input->getArgument(self::NAME_INPUT);
        $baseDir = rtrim((string) $input->getOption(self::DIR_INPUT), self::SLASH_CHAR);
        $force   = (bool) $input->getOption(self::FORCE_INPUT);

        $context = Str::studly($raw);
        if ($context === '') {
            $output->writeln('<error>Invalid context name.</error>');
            return Command::INVALID;
        }

        $fs   = new Filesystem();
        $root = $baseDir . self::SLASH_CHAR . $context;

        $paths = [
            $root,
            $root . self::DOMAIN_PATH,
            $root . self::APPLICATION_PATH,
            $root . self::INFRASTRUCTURE_PATH,
            $root . self::UI_PATH,
        ];

        foreach ($paths as $p) {
            if ($fs->exists($p) && !$force) {
                $output->writeln("<comment>• Exists:</comment> $p");
                continue;
            }
            $fs->mkdir($p);
            $output->writeln("<info>✔ Created:</info> $p");
        }

        $output->writeln("<info>$context context ready.</info>");
        return Command::SUCCESS;
    }
}
