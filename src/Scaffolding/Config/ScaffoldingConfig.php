<?php

declare(strict_types=1);

namespace DddForge\Scaffolding\Config;

class ScaffoldingConfig
{
    /**
     * @param string[] $successMessages
     */
    public function __construct(
        public string $name,
        public string $type,
        public bool $force,
        public bool $withSubLayers,
        public string $baseDir,
        public string $contextName,
        public ?string $template,
        public array $successMessages = [],
        public ?string $tipMessage = null,
    ) {
    }

    public static function forContext(
        string $name,
        string $baseDir,
        bool $force,
        bool $withSubLayers,
        ?string $template,
    ): self {
        return new self(
            $name,
            'context',
            $force,
            $withSubLayers,
            $baseDir,
            $name,
            $template,
            [
                '🎉 Your bounded context is ready!',
                '',
                'Next steps:',
                '• Add your domain models in the Domain layer',
                '• Create application services in the Application layer',
                '• Implement infrastructure adapters',
                '• Build your user interface'
            ],
            'Tip: Use --template=standard for more detailed structures.'
        );
    }

    public function templateInfo(): string
    {
        return $this->template
            ? " (<info>$this->template</info> template)"
            : '';
    }

    public function templateText(): string
    {
        return $this->template
            ? " using $this->template template"
            : '';
    }
}
