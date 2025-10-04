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
        string $contextName,
        string $baseDir,
        bool $withSublayers = false,
        ?string $template = null,
        array $customSublayers = []
    ): array {
        $root = $baseDir . self::DIRECTORY_SEPARATOR . $contextName;
        $paths = [$root];

        foreach (self::LAYER_PATHS as $layerPath) {
            $paths[] = $root . $layerPath;
        }

        if ($withSublayers) {
            $sublayers = $this->resolveSublayers($template, $customSublayers);

            foreach ($sublayers as $layer => $layerSublayers) {
                foreach ($layerSublayers as $sublayer) {
                    $paths[] = $root . '/' . $layer . '/' . $sublayer;
                }
            }
        }

        return $paths;
    }

    public function resolveSublayers(?string $template, array $customSublayers = []): array
    {
        if (!empty($customSublayers)) {
            return $customSublayers;
        }

        if ($template !== null && $this->templateEngine->isValidTemplate($template)) {
            $templateData = $this->templateEngine->getTemplate($template);
            return $templateData['sublayers'] ?? [];
        }

        $standardTemplate = $this->templateEngine->getTemplate('standard');
        return $standardTemplate['sublayers'] ?? [];
    }

    public function getLayerPaths(): array
    {
        return self::LAYER_PATHS;
    }

    public function groupPathsByLayer(array $paths, string $contextName): array
    {
        $grouped = ['root' => []];

        foreach ($paths as $path) {
            $relativePath = str_replace($contextName . '/', '', basename(dirname($path)) . '/' . basename($path));
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
