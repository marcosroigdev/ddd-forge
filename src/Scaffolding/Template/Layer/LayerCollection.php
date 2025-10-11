<?php

declare(strict_types=1);

namespace DddForge\Scaffolding\Template\Layer;

use DddForge\Scaffolding\Template\Layer\Exception\LayerException;
use DddForge\Scaffolding\Template\Layer\Exception\SubLayerException;
use DddForge\Support\Collection\TypedCollection;

/**
 * @extends TypedCollection<Layer>
 */
class LayerCollection extends TypedCollection
{
    protected function type(): string
    {
        return Layer::class;
    }

    public static function fromArray(array $layers): LayerCollection
    {
        $layerCollection = LayerCollection::createEmpty();

        foreach ($layers as $name => $subLayers) {
            self::validateLayerName($name);
            self::validateSubLayers($subLayers);

            $subLayerCollection = self::createSubLayerCollection($subLayers);

            $layerCollection->add(
                new Layer(
                    $name,
                    $subLayerCollection
                )
            );
        }

        return $layerCollection;
    }

    private static function validateLayerName(mixed $name): void
    {
        if (!is_string($name)) {
            throw LayerException::becauseLayerNameIsInvalid($name);
        }

        if (empty(trim($name))) {
            throw LayerException::becauseLayerNameCannotBeEmpty();
        }
    }

    private static function validateSubLayers(mixed $subLayers): void
    {
        if (!is_array($subLayers)) {
            throw SubLayerException::becauseSubLayersIsNotArray();
        }

        foreach ($subLayers as $subLayer) {
            if (!is_string($subLayer)) {
                throw SubLayerException::becauseSubLayerNameIsInvalid($subLayer);
            }

            if (empty(trim($subLayer))) {
                throw SubLayerException::becauseSubLayerNameCannotBeEmpty();
            }
        }
    }

    private static function createSubLayerCollection(array $subLayers): SubLayerCollection
    {
        $subLayerCollection = SubLayerCollection::createEmpty();

        foreach ($subLayers as $subLayer) {
            $subLayerCollection->add(new SubLayer($subLayer));
        }

        return $subLayerCollection;
    }
}
