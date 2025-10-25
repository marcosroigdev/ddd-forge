<?php

declare(strict_types=1);

namespace DddForge\Config;

final readonly class ForgePaths
{
    private const  BASE_PATH           = '.ddd-forge';
    private const  DIRECTORY_SEPARATOR = '/';

    public static function basePath(): string
    {
        return self::DIRECTORY_SEPARATOR . self::BASE_PATH;
    }

    public static function structure(): string
    {
        return self::basePath() . self::DIRECTORY_SEPARATOR . 'structure' . self::DIRECTORY_SEPARATOR;
    }
}
