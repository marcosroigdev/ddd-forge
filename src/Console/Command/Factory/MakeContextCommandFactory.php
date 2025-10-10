<?php

declare(strict_types=1);

namespace DddForge\Console\Command\Factory;

use DddForge\Console\Command\MakeContext\Input\InputTemplateValidator;
use DddForge\Console\Command\MakeContextCommand;
use DddForge\Scaffolding\CommandParam\Mode\DryRunManager;
use DddForge\Scaffolding\CommandParam\Mode\InteractiveWizard;
use DddForge\Scaffolding\Directory\DirectoryManager;
use DddForge\Scaffolding\Directory\DirectoryStructureBuilder;
use DddForge\Scaffolding\File\PresetManager;
use DddForge\Scaffolding\File\YamlExporter;
use DddForge\Scaffolding\Template\TemplateEngine;
use Symfony\Component\Filesystem\Filesystem;

final class MakeContextCommandFactory
{
    public static function create(): MakeContextCommand
    {
        $filesystem             = new Filesystem();
        $templateEngine         = new TemplateEngine();
        $structureBuilder       = new DirectoryStructureBuilder($templateEngine);
        $presetManager          = new PresetManager($filesystem);
        $wizard                 = new InteractiveWizard($templateEngine, $structureBuilder);
        $yamlExporter           = new YamlExporter($filesystem);
        $directoryManager       = new DirectoryManager($filesystem);
        $inputTemplateValidator = new InputTemplateValidator($templateEngine);
        $dryRunManager          = new DryRunManager($structureBuilder);

        return new MakeContextCommand(
            $presetManager,
            $structureBuilder,
            $wizard,
            $yamlExporter,
            $directoryManager,
            $inputTemplateValidator,
            $templateEngine,
            $dryRunManager
        );
    }
}
