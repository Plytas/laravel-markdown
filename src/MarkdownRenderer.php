<?php

namespace Spatie\LaravelMarkdown;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Block\Heading;
use League\CommonMark\Extension\ExtensionInterface;
use League\CommonMark\MarkdownConverter;
use League\CommonMark\Output\RenderedContentInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use Spatie\CommonMarkShikiHighlighter\HighlightCodeExtension;
use Spatie\LaravelMarkdown\Renderers\AnchorHeadingRenderer;

class MarkdownRenderer
{
    protected array $commonmarkOptions;
    protected bool $highlightCode;
    protected string $highlightTheme;
    protected ?string $cacheStoreName;
    protected bool $renderAnchors;
    protected array $extensions;
    protected array $blockRenderers;
    protected array $inlineRenderers;
    
    public function __construct(
        array $commonmarkOptions = [],
        bool $highlightCode = true,
        string $highlightTheme = 'github-light',
        ?string $cacheStoreName = null,
        bool $renderAnchors = true,
        array $extensions = [],
        array $blockRenderers = [],
        array $inlineRenderers = []
    ) {
        $this->commonmarkOptions = $commonmarkOptions;
        $this->highlightCode = $highlightCode;
        $this->highlightTheme = $highlightTheme;
        $this->cacheStoreName = $cacheStoreName;
        $this->renderAnchors = $renderAnchors;
        $this->extensions = $extensions;
        $this->blockRenderers = $blockRenderers;
        $this->inlineRenderers = $inlineRenderers;
    }

    public function commonmarkOptions(array $options): self
    {
        $this->commonmarkOptions = $options;

        return $this;
    }

    public function highlightCode(bool $highlightCode = true): self
    {
        $this->highlightCode = $highlightCode;

        return $this;
    }

    public function disableHighlighting(): self
    {
        $this->highlightCode = false;

        return $this;
    }

    public function highlightTheme(string $highlightTheme): self
    {
        $this->highlightTheme = $highlightTheme;

        return $this;
    }

    public function cacheStoreName(?string $cacheStoreName): self
    {
        $this->cacheStoreName = $cacheStoreName;

        return $this;
    }

    public function renderAnchors(bool $renderAnchors): self
    {
        $this->renderAnchors = $renderAnchors;

        return $this;
    }

    public function disableAnchors(): self
    {
        $this->renderAnchors = false;

        return $this;
    }

    public function addExtension(ExtensionInterface $extension): self
    {
        $this->extensions[] = $extension;

        return $this;
    }

    public function addBlockRenderer(string $blockClass, NodeRendererInterface $blockRenderer): self
    {
        $this->blockRenderers[] = ['class' => $blockClass, 'renderer' => $blockRenderer];

        return $this;
    }

    public function addInlineRenderer(string $inlineClass, NodeRendererInterface $inlineRenderer): self
    {
        $this->inlineRenderers[] = ['class' => $inlineClass, 'renderer' => $inlineRenderer];

        return $this;
    }

    public function toHtml(string $markdown): string
    {
        if ($this->cacheStoreName === false) {
            return $this->convertMarkdownToHtml($markdown);
        }

        $cacheKey = $this->getCacheKey($markdown);

        return cache()
            ->store($this->cacheStoreName)
            ->rememberForever($cacheKey, function () use ($markdown) {
                return $this->convertMarkdownToHtml($markdown);
            });
    }

    protected function getCacheKey(string $markdown): string
    {
        $options = json_encode([
            'theme' => $this->highlightTheme,
            'render_anchors' => $this->renderAnchors,
            'commonmark_options' => $this->commonmarkOptions,
        ]);

        return md5("markdown{$markdown}{$options}");
    }

    protected function convertMarkdownToHtml(string $markdown): string
    {
        return $this->getMarkdownConverter()->convertToHtml($markdown);
    }

    protected function configureCommonMarkEnvironment(EnvironmentBuilderInterface $environment): void
    {
        $environment->addExtension(new CommonMarkCoreExtension());
        if ($this->highlightCode) {
            $environment->addExtension(new HighlightCodeExtension($this->highlightTheme));
        }

        if ($this->renderAnchors) {
            $environment->addRenderer(Heading::class, new AnchorHeadingRenderer());
        }

        foreach ($this->extensions as $extension) {
            if (is_string($extension) && class_exists($extension)) {
                $extension = new $extension();
            }
            $environment->addExtension($extension);
        }

        foreach ($this->blockRenderers as $blockRenderer) {
            $environment->addRenderer($blockRenderer['class'], $blockRenderer['renderer']);
        }

        foreach ($this->inlineRenderers as $inlineRenderer) {
            $environment->addRenderer($inlineRenderer['class'], $inlineRenderer['renderer']);
        }
    }

    private function getMarkdownConverter(): MarkdownConverter
    {
        $environment = new Environment($this->commonmarkOptions);
        $this->configureCommonMarkEnvironment($environment);

        return new MarkdownConverter(
            environment: $environment
        );
    }

    public function convertToHtml(string $markdown): RenderedContentInterface
    {
        return $this->getMarkdownConverter()->convertToHtml($markdown);
    }
}
