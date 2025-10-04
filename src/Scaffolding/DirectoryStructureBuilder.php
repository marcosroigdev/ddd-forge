<?php

declare(strict_types=1);

namespace DddForge\Scaffolding;

final class DirectoryStructureBuilder
{
    private const DIRECTORY_SEPARATOR = '/';
    private const LAYER_PATHS = [
        'Domain' => '/Domain',
        'Application' => '/Application',
        'Infrastructure' => '/Infrastructure',
        'UI' => '/UI',
    ];

    public function __construct(
        private readonly TemplateEngine $templateEngine
    ) {}

    public function build(
        string $name,
        string $baseDir,
        array $layers,
        bool $withSublayers = false,
        ?string $template = null,
        array $customSublayers = []
    ): array {
        $root = $baseDir . self::DIRECTORY_SEPARATOR . $name;
        $paths = [$root];

        foreach ($layers as $layerPath) {
            $paths[] = $root . $layerPath;
        }

        if ($withSublayers) {
            $sublayers = $this->resolveSublayers($template, $customSublayers, $layers);

            foreach ($sublayers as $layer => $layerSublayers) {
                foreach ($layerSublayers as $sublayer) {
                    $paths[] = $root . '/' . $layer . '/' . $sublayer;
                }
            }
        }

        return $paths;
    }

    public function resolveSublayers(?string $template, array $customSublayers = [], array $defaultLayers = []): array
    {
        if (!empty($customSublayers)) {
            return $customSublayers;
        }

        if ($template !== null && $this->templateEngine->isValidTemplate($template)) {
            $templateData = $this->templateEngine->getTemplate($template);
            return $templateData['sublayers'] ?? [];
        }

        return [];
    }

    public function getLayerPaths(): array
    {
        return self::LAYER_PATHS;
    }

    public function groupPathsByLayer(array $paths, string $name): array
    {
        $grouped = ['root' => []];

        foreach ($paths as $path) {
            $relativePath = str_replace($name . '/', '', basename(dirname($path)) . '/' . basename($path));
            $parts = explode('/', trim($relativePath, '/'));

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
