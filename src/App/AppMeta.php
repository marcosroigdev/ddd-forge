<?php

declare(strict_types=1);

namespace DddForge\App;

use Composer\InstalledVersions;
use Throwable;

final class AppMeta
{
    private const NAME = 'DDD-Forge';
    private const DEFAULT_VERSION = 'dev';
    private static ?string $cachedVersion = null;

    public static function version(): string
    {
        if (self::$cachedVersion !== null) {
            return self::$cachedVersion;
        }

        self::$cachedVersion = self::resolveVersion();

        return self::$cachedVersion;
    }

    public static function name(): string
    {
        return self::NAME;
    }

    private static function resolveVersion(): string
    {
        if (!class_exists(InstalledVersions::class)) {
            return self::DEFAULT_VERSION;
        }

        try {
            $rootPackage = InstalledVersions::getRootPackage();
            return $rootPackage['pretty_version'];
        } catch (Throwable) {
            return self::DEFAULT_VERSION;
        }
    }
}
