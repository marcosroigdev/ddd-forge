<?php

declare(strict_types=1);

namespace DddForge\Console\Command\Factory;

use DddForge\Console\Command\MakeContextCommand;
use DddForge\Scaffolding\DirectoryManager;
use DddForge\Scaffolding\DirectoryStructureBuilder;
use DddForge\Scaffolding\DryRunManager;
use DddForge\Scaffolding\InputValidator;
use DddForge\Scaffolding\InteractiveWizard;
use DddForge\Scaffolding\PresetManager;
use DddForge\Scaffolding\TemplateEngine;
use DddForge\Scaffolding\YamlExporter;

final class MakeContextCommandFactory
{
    public static function create(): MakeContextCommand
    {
        $templateEngine = new TemplateEngine();
        $structureBuilder = new DirectoryStructureBuilder($templateEngine);
        $presetManager = new PresetManager();
        $wizard = new InteractiveWizard($templateEngine, $structureBuilder);
        $yamlExporter = new YamlExporter();
        $directoryManager = new DirectoryManager();
        $validator = new InputValidator($templateEngine);
        $dryRunManager = new DryRunManager($structureBuilder);

        return new MakeContextCommand(
            $presetManager,
            $structureBuilder,
            $wizard,
            $yamlExporter,
            $directoryManager,
            $validator,
            $templateEngine,
            $dryRunManager
        );
    }
}
