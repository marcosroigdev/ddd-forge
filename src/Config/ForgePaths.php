<?php

declare(strict_types=1);

namespace DddForge\Config;

readonly final class ForgePaths
{
    public const BASE_PATH = '/.ddd-forge';

    public static function structure(): string
    {
        return self::BASE_PATH . '/structure/';
    }
}
