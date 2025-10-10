<?php

declare(strict_types=1);

namespace DddForge\Support\Collection;

use DddForge\Support\Contracts\Arrayable;

/**
 * @template T
 * @implements Arrayable<T>
 * @phpstan-consistent-constructor
 */
abstract class Collection implements Arrayable
{
    /**
     * @var T[]
     */
    protected array $items;

    /**
     * @param T[] $items
     */
    public function __construct(array $items)
    {
        $this->items = $items;
    }


    /**
     * @param T[] $items
     * @phpstan-return static
     */
    public static function create(array $items): static
    {
        return new static($items);
    }

    /**
     * @phpstan-return static
     */
    public static function createEmpty(): static
    {
        return new static([]);
    }

    /**
     * @return T[]
     */
    public function toArray(): array
    {
        return array_map(
            fn ($item) => $item,
            $this->items
        );
    }
}
