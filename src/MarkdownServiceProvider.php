<?php

namespace Spatie\LaravelMarkdown;

use Illuminate\Support\Facades\Blade;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class MarkdownServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-markdown')
            ->hasConfigFile()
            ->hasViews();

        Blade::component('markdown', MarkdownBladeComponent::class);

        $this->app->bind(MarkdownRenderer::class, function () {
            $config = config('markdown');

            /** @var \Spatie\LaravelMarkdown\MarkdownRenderer $renderer */
            return new $config['renderer_class'](
                $config['commonmark_options'],
                $config['code_highlighting']['enabled'],
                $config['code_highlighting']['theme'],
                $config['cache_store'],
                $config['add_anchors_to_headings'],
                $config['extensions'],
                $config['block_renderers'],
                $config['inline_renderers'],
            );
        });
    }
}
