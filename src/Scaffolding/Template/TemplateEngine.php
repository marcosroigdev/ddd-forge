<?php

declare(strict_types=1);

namespace DddForge\Scaffolding\Template;

use DddForge\Scaffolding\Template\Layer\Layer;
use DddForge\Scaffolding\Template\Layer\LayerCollection;
use DddForge\Scaffolding\Template\Layer\SubLayer;
use DddForge\Scaffolding\Template\Layer\SubLayerCollection;
use DddForge\Support\Collection\StringCollection;

final class TemplateEngine
{
    private const         CUSTOM_TEMPLATE_DESCRIPTION = 'Custom (I\'ll choose my own sublayers)';
    private const         CUSTOM_TEMPLATE_NAME        = 'custom';
    private const         TEMPLATES                   = [
        'basic'          => [
            'name'        => 'Basic DDD (Main layers only)',
            'description' => 'Creates only the 4 main DDD layers without sublayers',
            'layers'      => [],
        ],
        'standard'       => [
            'name'        => 'Standard DDD (Recommended)',
            'description' => 'Complete DDD structure with common sublayers',
            'layers'      => [
                'Domain'         => ['Model', 'Service', 'Repository', 'Event'],
                'Application'    => ['Command', 'Query', 'Handler', 'Service'],
                'Infrastructure' => ['Persistence', 'Service', 'Resources'],
                'UI'             => ['Controller', 'Command'],
            ],
        ],
        'cqrs'           => [
            'name'        => 'CQRS Pattern',
            'description' => 'Command Query Responsibility Segregation',
            'layers'      => [
                'Domain'         => ['Read', 'Write', 'Event'],
                'Application'    => ['Command', 'Query', 'Handler', 'Bus'],
                'Infrastructure' => ['Read', 'Write', 'Persistence', 'Resources'],
                'UI'             => ['Controller', 'Command'],
            ],
        ],
        'event-sourcing' => [
            'name'        => 'Event Sourcing',
            'description' => 'Event-driven architecture with event store',
            'layers'      => [
                'Domain'         => ['Aggregate', 'Event', 'Projection'],
                'Application'    => ['Command', 'Query', 'EventHandler', 'Projector'],
                'Infrastructure' => ['EventStore', 'Projection', 'Snapshot', 'Resources'],
                'UI'             => ['Controller', 'Command'],
            ],
        ],
        'hexagonal'      => [
            'name'        => 'Hexagonal Architecture',
            'description' => 'Ports and Adapters pattern',
            'layers'      => [
                'Domain'         => ['Model', 'Port', 'Service'],
                'Application'    => ['UseCase', 'Port', 'Service'],
                'Infrastructure' => ['Adapter', 'Persistence', 'External', 'Resources'],
                'UI'             => ['Adapter', 'Controller', 'Command'],
            ],
        ],
    ];

    public function getTemplate(string $name): Template
    {
        return $this->getTemplateCollection()->findByNameOrFail($name);
    }

    public function isValidTemplate(string $template): bool
    {
        $templates                             = self::TEMPLATES;
        $templates[self::CUSTOM_TEMPLATE_NAME] = [];
        return isset($templates[$template]);
    }

    public function getTemplateNames(): StringCollection
    {
        return StringCollection::create(array_keys(self::TEMPLATES));
    }

    public function buildTemplateHelp(): string
    {
        $help = [];
        foreach (self::TEMPLATES as $key => $template) {
            $help[] = "  • <info>$key</info>: {$template['description']}";
        }
        return implode(PHP_EOL, $help);
    }

    public function getTemplateChoices(): StringCollection
    {
        return $this->getTemplateCollection()->getTemplatesNames();
    }

    public function getTemplateCollection(): TemplateCollection
    {
        $collection = TemplateCollection::createEmpty();

        $collection->add($this->getCustomTemplate());

        foreach (self::TEMPLATES as $name => $structure) {

            $layerCollection = $this->getLayerCollection($structure['layers']);

            $collection->add(
                new Template(
                    $name,
                    $layerCollection,
                    $structure['description']
                )
            );
        }

        return $collection;
    }

    private function getCustomTemplate(): Template
    {
        return new Template(
            self::CUSTOM_TEMPLATE_NAME,
            LayerCollection::createEmpty(),
            self::CUSTOM_TEMPLATE_DESCRIPTION
        );
    }

    /**
     * @param array<string, string[]> $layerItems
     */
    private function getLayerCollection(array $layerItems): LayerCollection
    {
        $layerCollection = LayerCollection::createEmpty();

        foreach ($layerItems as $sublayerName => $sublayerItems) {
            $sublayerCollection = $this->getSubLayerCollection($sublayerItems);

            $layerCollection->add(
                new Layer(
                    $sublayerName,
                    $sublayerCollection
                )
            );
        }

        return $layerCollection;
    }

    /**
     * @param string[] $sublayerItems
     */
    private function getSubLayerCollection(array $sublayerItems): SublayerCollection
    {
        $sublayerCollection = SublayerCollection::createEmpty();

        foreach ($sublayerItems as $sublayerItem) {
            $sublayerCollection->add(
                new SubLayer($sublayerItem)
            );
        }

        return $sublayerCollection;
    }
}
