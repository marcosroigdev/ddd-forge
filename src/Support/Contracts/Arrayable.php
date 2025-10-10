<?php

declare(strict_types=1);

namespace DddForge\Support\Contracts;

/**
 * @template T
 */
interface Arrayable
{
    /**
     * @return T[]
     */
    public function toArray(): array;
}
