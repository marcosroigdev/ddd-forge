<?php
declare(strict_types=1);

namespace DddForge\Console\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(name: 'init', description: 'Initialize hexagonal project structure')]
final class InitCommand extends Command
{
    protected function configure(): void
    {
        $this->addOption('framework', null, InputOption::VALUE_OPTIONAL, 'symfony|laravel', 'symfony');
        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Overwrite if exists');
        $this->addOption('dir', null, InputOption::VALUE_OPTIONAL, 'Target directory', 'src');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dir = (string) $input->getOption('dir');
        $fs = new Filesystem();

        $paths = [
            "$dir/Domain",
            "$dir/Application",
            "$dir/Infrastructure",
            "$dir/UI",
        ];

        foreach ($paths as $p) {
            if (!$fs->exists($p)) {
                $fs->mkdir($p);
                $output->writeln("<info>✔ Created:</info> $p");
            } else {
                $output->writeln("<comment>• Exists:</comment> $p");
            }
        }

        $output->writeln("\n<info>DDD-Forge initialized.</info>");
        return Command::SUCCESS;
    }
}
