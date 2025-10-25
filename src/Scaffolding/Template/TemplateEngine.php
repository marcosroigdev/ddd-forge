<?php

declare(strict_types=1);

namespace DddForge\Scaffolding\Template;

use DddForge\Scaffolding\Template\Layer\Layer;
use DddForge\Scaffolding\Template\Layer\LayerCollection;
use DddForge\Scaffolding\Template\Layer\SubLayer;
use DddForge\Scaffolding\Template\Layer\SubLayerCollection;
use DddForge\Support\Collection\StringCollection;
use DddForge\Support\Yaml\YamlLoader;

final class TemplateEngine
{
    private const CUSTOM_TEMPLATE_DESCRIPTION = 'Custom (I\'ll choose my own sublayers)';
    private const CUSTOM_TEMPLATE_NAME        = 'custom';
    private const CONTEXT_TEMPLATE_FILE_PATH  = '/src/Config/Templates/context.yaml';

    public function __construct(
        private readonly YamlLoader $yamlLoader
    ) {
    }

    public function getTemplate(string $name): Template
    {
        return $this->getTemplateCollection()->findByNameOrFail($name);
    }

    /**
     * @return array<string, mixed>
     */
    private function getContextTemplatesArray(): array
    {
        return $this->yamlLoader->load(self::CONTEXT_TEMPLATE_FILE_PATH);
    }

    public function isValidTemplate(string $template): bool
    {
        $templates                             = $this->getContextTemplatesArray()['templates'];
        $templates[self::CUSTOM_TEMPLATE_NAME] = [];
        return isset($templates[$template]);
    }

    public function getTemplateNames(): StringCollection
    {
        $templates = $this->getContextTemplatesArray()['templates'];
        /** @var array<string> $keys */
        $keys = array_keys($templates);
        return StringCollection::create($keys);
    }

    public function buildTemplateHelp(): string
    {
        $help = [];
        foreach ($this->getContextTemplatesArray()['templates'] as $key => $template) {
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

        foreach ($this->getContextTemplatesArray()['templates'] as $name => $structure) {
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
