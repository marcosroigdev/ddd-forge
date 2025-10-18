<?php

declare(strict_types=1);

namespace DddForge\Scaffolding\Directory;

use DddForge\Scaffolding\Template\Layer\LayerCollection;
use DddForge\Scaffolding\Template\TemplateEngine;

final class DirectoryStructureBuilder
{
    private const DIRECTORY_SEPARATOR = '/';

    public function __construct(
        private readonly TemplateEngine $templateEngine,
        private readonly DirectoryPathRegistry $directoryPathRegistry
    ) {
    }

    /**
     * @return string[]
     */
    public function build(DirectoryBuildConfig $config): array
    {
        $root  = $config->baseDir . self::DIRECTORY_SEPARATOR . $config->name;
        $paths = [$root];

        foreach ($config->directoryPaths->toArray() as $directoryPath) {
            $paths[] = $root . $directoryPath->path;
        }

        if ($config->withSublayers) {
            $paths = array_merge($paths, $this->buildSublayerPaths($config, $root));
        }

        return $paths;
    }

    /**
     * @return string[]
     */
    private function buildSublayerPaths(DirectoryBuildConfig $config, string $root): array
    {
        $paths           = [];
        $layerCollection = $config->customSublayers;

        if ($config->template && $layerCollection->isEmpty()) {
            $layerCollection = $this->getTemplateLayers($config->template);
        }

        foreach ($layerCollection->toArray() as $layer) {
            foreach ($layer->subLayers->toArray() as $sublayer) {
                $paths[] = $root . '/' . $layer->name . '/' . $sublayer->name;
            }
        }

        return $paths;
    }

    public function getTemplateLayers(string $templateName): LayerCollection
    {
        return $this->templateEngine->getTemplate($templateName)->layers;
    }

    public function getDefaultDirectoryPaths(): DirectoryPathCollection
    {
        return $this->directoryPathRegistry->getDefaultPaths();
    }

    /**
     * @param string[] $paths
     */
    public function buildDirectoryGroups(array $paths, string $name): DirectoryGroupCollection
    {
        $directoryGroupCollection = DirectoryGroupCollection::createEmpty();

        $directoryGroupCollection->add(
            new DirectoryGroup(
                'root',
                PathCollection::createEmpty(),
            )
        );

        foreach ($paths as $path) {
            $relativePath = str_replace($name . '/', '', basename(dirname($path)) . '/' . basename($path));
            $parts        = explode('/', trim($relativePath, '/'));

            if (count($parts) === 1) {
                $directoryGroupCollection->findByName('root')?->paths->add(
                    new Path(
                        $path
                    )
                );
            } else {
                $layer = $parts[0];
                if (!$directoryGroupCollection->findByName($layer)) {
                    $directoryGroupCollection->add(
                        new DirectoryGroup(
                            $layer,
                            PathCollection::createEmpty(),
                        )
                    );
                }

                if (count($parts) > 1) {
                    $directoryGroupCollection->findByName($layer)?->paths->add(
                        new Path(
                            $path
                        )
                    );
                }
            }
        }

        return $directoryGroupCollection;
    }
}
