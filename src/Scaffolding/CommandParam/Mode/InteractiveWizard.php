<?php

declare(strict_types=1);

namespace DddForge\Scaffolding\CommandParam\Mode;

use DddForge\Scaffolding\Directory\DirectoryStructureBuilder;
use DddForge\Scaffolding\Template\Layer\LayerCollection;
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
     * @param array{nameArgument?: string, namePrompt?: string, title: string, description?: string|string[]} $config
     */
    public function run(SymfonyStyle $io, InputInterface $input, array $config): LayerCollection
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

        $customSubLayers = null;

        if ($selectedTemplate === 'custom') {
            $io->section('Custom Sublayer Configuration');
            $customSubLayers = $this->configureCustomSubLayers($io);
            $input->setOption('with-sublayers', true);
        } elseif ($selectedTemplate !== 'basic') {
            $template = $this->templateEngine->getTemplate($selectedTemplate);
            $customSubLayers = $template->layers;
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

        return is_null($customSubLayers) ? LayerCollection::createEmpty() : $customSubLayers;
    }


    public function configureCustomSubLayers(SymfonyStyle $io): LayerCollection
    {
        $io->text('Configure subLayers for each main layer. Leave empty to skip a layer.');
        $io->newLine();

        $customSubLayers = [];
        $directoryPaths = $this->structureBuilder->getDefaultDirectoryPaths();

        foreach ($directoryPaths->toArray() as $directoryPath) {
            $io->section("$directoryPath->name Layer");

            $suggestions = $this->getSuggestionsForLayer($directoryPath->name);
            if (!empty($suggestions)) {
                $io->text("  <comment>Suggestions: " . implode(', ', $suggestions) . "</comment>");
            }

            $createSubLayers = $io->confirm("Create subLayers for $directoryPath->name?");

            if ($createSubLayers) {
                $subLayersInput = $io->ask(
                    "Enter sublayer names (comma-separated)",
                    implode(', ', $suggestions)
                );

                if ($subLayersInput) {
                    $subLayers = array_map('trim', explode(',', $subLayersInput));
                    $subLayers = array_filter($subLayers);
                    $customSubLayers[$directoryPath->name] = $subLayers;

                    $io->text("  ✓ Will create: <info>" . implode(', ', $subLayers) . "</info>");
                }
            }

            $io->newLine();
        }

        return LayerCollection::fromArray($customSubLayers);
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
