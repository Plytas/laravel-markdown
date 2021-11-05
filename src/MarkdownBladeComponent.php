<?php

namespace Spatie\LaravelMarkdown;

use Illuminate\View\Component;
use Illuminate\View\View;

class MarkdownBladeComponent extends Component
{
    protected ?array $options;
    protected ?bool $highlightCode;
    protected ?string $theme;
    protected ?bool $anchors;
    
    public function __construct(
        ?array $options = [],
        ?bool $highlightCode = null,
        ?string $theme = null,
        ?bool $anchors = null
    ) {
        $this->options = $options;
        $this->highlightCode = $highlighCode;
        $this->theme = $theme;
        $this->anchors = $anchors;
    }

    public function toHtml(string $markdown): string
    {
        $config = config('markdown');

        $markdownRenderer = new $config['renderer_class'](
            array_merge($config['commonmark_options'], $this->options),
            $this->highlightCode ?? $config['code_highlighting']['enabled'],
            $this->theme ?? $config['code_highlighting']['theme'],
            $config['cache_store'],
            $this->anchors ?? $config['add_anchors_to_headings'],
            $config['extensions'],
            $config['block_renderers'],
            $config['inline_renderers'],
        );

        return $markdownRenderer->toHtml($markdown);
    }

    public function render(): View
    {
        return view('markdown::markdown');
    }
}
