<?php

declare(strict_types=1);

namespace DddForge\Scaffolding\Template\Layer;

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

            $subLayerCollection = SubLayerCollection::createEmpty();

            foreach ($subLayers as $subLayer) {
                $subLayerCollection->add(
                    new SubLayer(
                        $subLayer
                    )
                );
            }

            $layerCollection->add(
                new Layer(
                    $name,
                    $subLayerCollection
                )
            );

        }

        return $layerCollection;
    }
}
