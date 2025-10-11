<?php

declare(strict_types=1);

namespace DddForge\Scaffolding\CommandParam\Mode;

use DddForge\Scaffolding\Directory\DirectoryStructureBuilder;
use DddForge\Scaffolding\Template\TemplateEngine;
use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

readonly class InteractiveWizard
{
    public function __construct(
        private TemplateEngine $templateEngine,
        private DirectoryStructureBuilder $structureBuilder
    ) {
    }

    /**
     * @param array{
     *     title: string,
     *     description?: string|string[],
     *     nameArgument?: string,
     *     namePrompt?: string,
     * } $config
     * @return array{
     *     customSublayers: array<string, string[]>,
     *     selectedTemplate: string
     * }
     */
    public function run(SymfonyStyle $io, InputInterface $input, array $config): array
    {
        $io->title("🏗️  {$config['title']}");
        $io->text($config['description'] ?? []);
        $io->newLine();

        $nameArgument = $config['nameArgument'] ?? 'name';
        $namePrompt = $config['namePrompt'] ?? 'What is the name?';

        if (!$input->getArgument($nameArgument)) {
            $name = $io->ask(
                $namePrompt,
                null,
                function ($answer) {
                    if (empty(trim($answer))) {
                        throw new RuntimeException('Name cannot be empty.');
                    }
                    return $answer;
                }
            );
            $input->setArgument($nameArgument, $name);
        }

        $currentDir = $input->getOption('dir');
        if ($io->confirm("Use default directory '$currentDir'?")) {
            $io->text("  → Using directory: <info>$currentDir</info>");
        } else {
            $customDir = $io->ask('Enter custom directory path', $currentDir);
            $input->setOption('dir', $customDir);
        }

        $io->newLine();

        $templateChoices = $this->templateEngine->getTemplateChoices();
        $selectedTemplate = $io->choice(
            'Choose your context architecture',
            array_map(
                fn (string $choice) => $choice,
                $templateChoices->toArray()
            ),
            'standard'
        );

        $customSublayers = [];

        if ($selectedTemplate === 'custom') {
            $io->section('Custom Sublayer Configuration');
            $customSublayers = $this->configureCustomSublayers($io);
            $input->setOption('with-sublayers', true);
        } elseif ($selectedTemplate !== 'basic') {
            $template = $this->templateEngine->getTemplate($selectedTemplate);
            $customSublayers = $template->layers;
            $input->setOption('with-sublayers', true);
            $input->setOption('template', $selectedTemplate);

            $io->text("  ✓ Using template: <info>$template->name</info>");
            $io->text("  <comment>$template->description</comment>");
        }

        $io->newLine();

        if ($io->confirm('Preview structure before creating? (dry-run)')) {
            $input->setOption('dry-run', true);
        }

        if ($io->confirm('Save this configuration as a preset for future use?', false)) {
            $presetName = $io->ask('Enter preset name', $input->getArgument('name'));
            $input->setOption('save-preset', $presetName);
        }

        if ($io->confirm('Create .gitkeep files in all directories?')) {
            $input->setOption('gitkeep', true);
        }

        $io->newLine();

        return [
            'customSublayers' => $customSublayers,
            'selectedTemplate' => $selectedTemplate
        ];
    }

    /**
     * @return array<string, string[]>
     */
    public function configureCustomSublayers(SymfonyStyle $io): array
    {
        $io->text('Configure sublayers for each main layer. Leave empty to skip a layer.');
        $io->newLine();

        $customSublayers = [];
        $layerPaths = $this->structureBuilder->getLayerPaths();

        foreach ($layerPaths as $layerName => $layerPath) {
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
                    $customSublayers[$layerName] = $sublayers;

                    $io->text("  ✓ Will create: <info>" . implode(', ', $sublayers) . "</info>");
                }
            }

            $io->newLine();
        }

        return $customSublayers;
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
}
