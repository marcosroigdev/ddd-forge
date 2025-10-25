<?php

declare(strict_types=1);

namespace DddForge\Support\Yaml;

use RuntimeException;
use Symfony\Component\Yaml\Yaml;

final class YamlLoader
{
    private const DIRECTORY_SEPARATOR = '/';

    private readonly string $baseDir;

    public function __construct(?string $baseDir = null)
    {
        $this->baseDir = $baseDir ?? dirname(__DIR__, 3);
    }

    /**
     * @return array<string, mixed>
     */
    public function load(string $relativePath): array
    {
        $filePath = $this->baseDir . self::DIRECTORY_SEPARATOR . ltrim($relativePath, self::DIRECTORY_SEPARATOR);

        if (!file_exists($filePath)) {
            throw new RuntimeException("Yaml file not found: $filePath");
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new RuntimeException("Could not read Yaml file: $filePath");
        }

        $data = Yaml::parse($content);

        if (!is_array($data)) {
            throw new RuntimeException("Invalid YAML in: $filePath");
        }

        return $data;
    }
}
