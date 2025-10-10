<?php

declare(strict_types=1);

namespace DddForge\Support\Utils;

final class Str
{
    private const REGEXP = '/[^A-Za-z0-9]+/';

    public static function studly(string $value): string
    {
        $v = preg_replace(self::REGEXP, ' ', $value);
        $v = ucwords(strtolower(trim((string) $v)));
        return str_replace(' ', '', $v);
    }
}
