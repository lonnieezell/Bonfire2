<?php

namespace Bonfire\View;

use RuntimeException;

class Metadata
{
    private string $title;
    private array $meta       = [];
    private array $link       = [];
    private array $script     = [];
    private array $rawScripts = [];
    private array $style      = [];
    private array $rawStyles  = [];

    public function __construct()
    {
        helper('setting');

        $this->title  = setting('Site.siteName');
        $this->meta[] = ['charset' => 'UTF-8'];
        $this->meta[] = ['name' => 'viewport', 'content' => 'width=device-width, initial-scale=1'];
    }

    /**
     * Renders out the html for the given meta type,
     * i.e. 'meta', 'title', 'link', 'script', 'rawScripts', 'style', 'rawStyles'.
     * Attribute values are escaped. The content of raw scripts and styles is not.
     */
    public function render(string $type): string
    {
        if (! isset($this->{$type})) {
            throw new RuntimeException('Metadata type not found');
        }

        if ($type === 'title') {
            return '<title>' . $this->title . '</title>';
        }

        $html = '';

        if ($type === 'rawScripts' || $type === 'rawStyles') {
            $tag = $type === 'rawScripts' ? 'script' : 'style';

            foreach ($this->{$type} as $content) {
                $html .= '<' . $tag . '>' . $content . '</' . $tag . '>';
            }

            return $html;
        }

        $content = $this->{$type};
        if ($type === 'style') {
            $type = 'link';
        }

        foreach ($content as $item) {
            $html .= '<' . $type . ' ';

            foreach ($item as $key => $value) {
                $html .= $key . '="' . htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '" ';
            }

            $html .= '>';

            if ($type === 'script') {
                $html .= '</script>';
            }

            $html .= "\n";
        }

        return $html;
    }

    /**
     * Set the title of the page.
     */
    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get the page title
     */
    public function title(): string
    {
        return $this->title;
    }

    /**
     * Add a meta tag to the page.
     * Can be used to add meta tags like 'description', 'keywords', 'author', etc.
     * and also to add custom meta tags.
     *
     * Example:
     * $this->addMeta(['description' => 'This is the description of the page']);
     * $this->addMeta(['property' => 'og:title', 'content' => 'This is the title of the page']);
     */
    public function addMeta(array $content): self
    {
        $this->pushUnique($this->meta, $content);

        return $this;
    }

    /**
     * Get all meta tags.
     */
    public function meta(): array
    {
        return $this->meta;
    }

    /**
     * Add a link tag to the page.
     * Can be used to add link tags like 'canonical', 'prev', 'next', etc.
     * and also to add custom link tags.
     *
     * Example:
     * $this->addLink(['rel' => 'canonical', 'href' => 'https://example.com/']);
     * $this->addLink(['rel' => 'icon', 'href' => 'favicon.ico', 'type' => 'image/x-icon']);
     */
    public function addLink(array $content): self
    {
        $this->pushUnique($this->link, $content);

        return $this;
    }

    /**
     * Get all link tags.
     */
    public function links(): array
    {
        return $this->link;
    }

    /**
     * Add a script tag to the page.
     * Can be used to add script tags like 'jquery', 'bootstrap', etc.
     * and also to add custom script tags.
     *
     * Example:
     * $this->addScript(['src' => 'https://example.com/js/jquery.min.js']);
     * $this->addScript(['src' => 'https://example.com/js/bootstrap.min.js', 'type' => 'text/javascript']);
     */
    public function addScript(array $content): self
    {
        $this->pushUnique($this->script, $content);

        return $this;
    }

    /**
     * Get all script tags.
     */
    public function scripts(): array
    {
        return $this->script;
    }

    public function addRawScript(string $content): self
    {
        $this->pushUnique($this->rawScripts, $content);

        return $this;
    }

    /**
     * Get all raw script content.
     */
    public function rawScripts(): array
    {
        return $this->rawScripts;
    }

    /**
     * Add the content of a style tag to the page, i.e. `<style>...</style>`.
     * The content is output as it is, so don't pass it anything a visitor typed.
     */
    public function addRawStyle(string $content): self
    {
        $this->pushUnique($this->rawStyles, $content);

        return $this;
    }

    /**
     * Get all raw style content.
     */
    public function rawStyles(): array
    {
        return $this->rawStyles;
    }

    /**
     * Add a style tag to the page.
     * Can be used to add style tags like 'bootstrap', etc.
     * and also to add custom style tags.
     *
     * Example:
     * $this->addStyle(['href' => 'https://example.com/css/bootstrap.min.css']);
     * $this->addStyle(['href' => 'https://example.com/css/style.css', 'type' => 'text/css']);
     */
    public function addStyle(array $content): self
    {
        $this->pushUnique($this->style, $content);

        return $this;
    }

    /**
     * Get all style tags.
     */
    public function styles(): array
    {
        return $this->style;
    }

    /**
     * Adds an item to a list unless the list already holds an identical one,
     * so that block partials can each declare the assets they need.
     */
    private function pushUnique(array &$list, array|string $item): void
    {
        if (! in_array($item, $list, true)) {
            $list[] = $item;
        }
    }
}
