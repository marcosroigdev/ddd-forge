<?php

declare(strict_types=1);

namespace DddForge\Support\Collection;

use DddForge\Support\Collection\Exception\InvalidCollectionTypeException;

/**
 * @template T
 * @extends Collection<T>
 */
abstract class TypedCollection extends Collection
{
    public function __construct(array $items)
    {
        $expectedType = $this->type();

        array_walk(
            $items,
            function (mixed $item) use($expectedType) {
                if (!$this->isValidType($item, $expectedType)) {
                    throw InvalidCollectionTypeException::becauseTypeIsInvalid(
                        $expectedType,
                        get_debug_type($item)
                    );
                }
            }
        );

        parent::__construct($items);
    }

    abstract protected function type(): string;

    /**
     * @param mixed $item
     * @param string $expectedType
     * @return bool
     */
    private function isValidType(mixed $item, string $expectedType): bool
    {
        return match ($expectedType) {
            'string' => is_string($item),
            'int'    => is_int($item),
            'float'  => is_float($item),
            'bool'   => is_bool($item),
            default  => $item instanceof $expectedType,
        };
    }
}
