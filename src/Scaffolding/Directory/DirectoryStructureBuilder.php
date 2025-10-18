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

    public function build(DirectoryBuildConfig $config): PathCollection
    {
        $paths = PathCollection::createEmpty();
        $root  = $config->baseDir . self::DIRECTORY_SEPARATOR . $config->name;

        $paths->add(
            new Path(
                $root
            )
        );

        foreach ($config->directoryPaths->toArray() as $directoryPath) {
            $paths->add(
                new Path(
                    $root . $directoryPath->path
                )
            );
        }

        if ($config->withSublayers) {
            $layerCollection = $config->customSublayers;

            if ($config->template && $layerCollection->isEmpty()) {
                $layerCollection = $this->getTemplateLayers($config->template);
            }

            foreach ($layerCollection->toArray() as $layer) {
                foreach ($layer->subLayers->toArray() as $sublayer) {
                    $paths->add(
                        new Path(
                            $root . '/' . $layer->name . '/' . $sublayer->name
                        )
                    );
                }
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

    public function buildDirectoryGroups(PathCollection $paths, string $name): DirectoryGroupCollection
    {
        $directoryGroupCollection = DirectoryGroupCollection::createEmpty();

        $directoryGroupCollection->add(
            new DirectoryGroup(
                'root',
                PathCollection::createEmpty(),
            )
        );

        foreach ($paths->toArray() as $path) {
            $relativePath = str_replace($name . '/', '', basename(dirname($path->name)) . '/' . basename($path->name));
            $parts        = explode('/', trim($relativePath, '/'));

            if (count($parts) === 1) {
                $directoryGroupCollection->findByName('root')?->paths->add($path);
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
                    $directoryGroupCollection->findByName($layer)?->paths->add($path);
                }
            }
        }

        return $directoryGroupCollection;
    }
}
