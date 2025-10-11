<?php

declare(strict_types=1);

namespace DddForge\Scaffolding\Directory;

use DddForge\Scaffolding\Template\Layer\LayerCollection;
use DddForge\Scaffolding\Template\TemplateEngine;

final class DirectoryStructureBuilder
{
    private const DIRECTORY_SEPARATOR = '/';
    private const LAYER_PATHS         = [
        'Domain'         => '/Domain',
        'Application'    => '/Application',
        'Infrastructure' => '/Infrastructure',
        'UI'             => '/UI',
    ];

    public function __construct(
        private readonly TemplateEngine $templateEngine
    ) {
    }

    /**
     * @param string[] $layers
     * @return string[]
     */
    public function build(
        string $name,
        string $baseDir,
        array $layers,
        LayerCollection $customSublayers,
        bool $withSublayers = false,
        ?string $template = null,
    ): array {
        $root  = $baseDir . self::DIRECTORY_SEPARATOR . $name;
        $paths = [$root];

        foreach ($layers as $layerPath) {
            $paths[] = $root . $layerPath;
        }
        if ($withSublayers) {
            $layerCollection = $customSublayers;

            if ($template && $layerCollection->isEmpty()) {
                $layerCollection = $this->getTemplateLayers($template);
            }

            foreach ($layerCollection->toArray() as $layer) {
                foreach ($layer->subLayers->toArray() as $sublayer) {
                    $paths[] = $root . '/' . $layer->name . '/' . $sublayer->name;
                }
            }
        }

        return $paths;
    }

    public function getTemplateLayers(string $templateName): LayerCollection
    {
        return $this->templateEngine->getTemplate($templateName)->layers;
    }

    /**
     * @return array<string, string>
     */
    public function getLayerPaths(): array
    {
        return self::LAYER_PATHS;
    }

    /**
     * @param string[] $paths
     * @return array<string, string[]>
     */
    public function groupPathsByLayer(array $paths, string $name): array
    {
        $grouped = ['root' => []];

        foreach ($paths as $path) {
            $relativePath = str_replace($name . '/', '', basename(dirname($path)) . '/' . basename($path));
            $parts        = explode('/', trim($relativePath, '/'));

            if (count($parts) === 1) {
                $grouped['root'][] = $path;
            } else {
                $layer = $parts[0];
                if (!isset($grouped[$layer])) {
                    $grouped[$layer] = [];
                }
                if (count($parts) > 1) {
                    $grouped[$layer][] = $path;
                }
            }
        }

        return $grouped;
    }
}
