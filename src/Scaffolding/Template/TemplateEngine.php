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
    private const TEMPLATES = [
        'basic'          => [
            'name'        => 'Basic DDD (Main layers only)',
            'description' => 'Creates only the 4 main DDD layers without sublayers',
            'sublayers'   => [],
        ],
        'standard'       => [
            'name'        => 'Standard DDD (Recommended)',
            'description' => 'Complete DDD structure with common sublayers',
            'sublayers'   => [
                'Domain'         => ['Model', 'Service', 'Repository', 'Event'],
                'Application'    => ['Command', 'Query', 'Handler', 'Service'],
                'Infrastructure' => ['Persistence', 'Service', 'Resources'],
                'UI'             => ['Controller', 'Command'],
            ],
        ],
        'cqrs'           => [
            'name'        => 'CQRS Pattern',
            'description' => 'Command Query Responsibility Segregation',
            'sublayers'   => [
                'Domain'         => ['Read', 'Write', 'Event'],
                'Application'    => ['Command', 'Query', 'Handler', 'Bus'],
                'Infrastructure' => ['Read', 'Write', 'Persistence', 'Resources'],
                'UI'             => ['Controller', 'Command'],
            ],
        ],
        'event-sourcing' => [
            'name'        => 'Event Sourcing',
            'description' => 'Event-driven architecture with event store',
            'sublayers'   => [
                'Domain'         => ['Aggregate', 'Event', 'Projection'],
                'Application'    => ['Command', 'Query', 'EventHandler', 'Projector'],
                'Infrastructure' => ['EventStore', 'Projection', 'Snapshot', 'Resources'],
                'UI'             => ['Controller', 'Command'],
            ],
        ],
        'hexagonal'      => [
            'name'        => 'Hexagonal Architecture',
            'description' => 'Ports and Adapters pattern',
            'sublayers'   => [
                'Domain'         => ['Model', 'Port', 'Service'],
                'Application'    => ['UseCase', 'Port', 'Service'],
                'Infrastructure' => ['Adapter', 'Persistence', 'External', 'Resources'],
                'UI'             => ['Adapter', 'Controller', 'Command'],
            ],
        ],
    ];

    /**
     * @return array{
     *     name: string,
     *     description: string,
     *     sublayers: array<string, string[]>
     * }|null
     */
    public function getTemplate(string $name): ?array
    {
        return self::TEMPLATES[$name] ?? null;
    }

    public function isValidTemplate(string $template): bool
    {
        return isset(self::TEMPLATES[$template]);
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

    /**
     * @return array<string, string>
     */
    public function getTemplateChoices(): array
    {
        $choices = ['custom' => 'Custom (I\'ll choose my own sublayers)'];
        foreach (self::TEMPLATES as $key => $template) {
            $choices[$key] = $template['name'];
        }
        return $choices;
    }

    public function getTemplateCollection(): TemplateCollection
    {
        $collection = TemplateCollection::createEmpty();

        $collection->add($this->getCustomTemplate());

        foreach (self::TEMPLATES as $name => $structure) {

            $layerCollection = $this->getLayerCollection($structure['sublayers']);

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
            'custom',
            LayerCollection::createEmpty(),
            'Custom (I\'ll choose my own sublayers)'
        );
    }

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
