<?php

use Bonfire\Content\Content;
use Bonfire\Content\ContentException;
use Bonfire\Content\Entry;
use Bonfire\Content\GlobalSet;
use Bonfire\Content\Markdown;
use Bonfire\Content\NavigationItem;
use Bonfire\Content\StaleCheck;
use Bonfire\Resources\Controllers\ResourceController;
use Bonfire\Resources\Resource;
use Bonfire\View\Theme;
use CodeIgniter\I18n\Time;
use CodeIgniter\Router\RouteCollection;

if (! function_exists('has_error')) {
    /**
     * Determines whether an error exists
     * for a form field. This requires the errors
     * are passed back like:
     *  return redirect()->back()->with('errors', $this->validation->getErrors());
     */
    function has_error(string $field): bool
    {
        if (! session()->has('errors')) {
            return false;
        }

        return isset(session('errors')[$field]);
    }
}

if (! function_exists('error')) {
    /**
     * Displays the error message for a single form field.
     */
    function error(string $field)
    {
        return session('errors')[$field] ?? '';
    }
}

if (! function_exists('app_date')) {
    /**
     * Formats a date according to the format
     * specified in the general settings page.
     *
     * It can take a string, a DateTime, or a Time instance.
     *
     * If $includeTimezone === true, will return the tz abbreviation
     * at the end of the date (i.e. CST, PST, etc)
     *
     * @param mixed $date
     *
     * @throws Exception
     */
    function app_date($date, bool $includeTime = false, bool $includeTimezone = false): string
    {
        $format = $includeTime
            ? [
                // values not by default in the Config\App, therefore need defaults
                setting('App.dateFormat') ?? 'Y-m-d',
                setting('App.timeFormat') ?? 'H:i:s',
                $includeTimezone ? 'T' : '',
            ]
            : [
                setting('App.dateFormat') ?? 'Y-m-d',
                $includeTimezone ? 'T' : '',
            ];

        $format = trim(implode(' ', $format));

        if (is_string($date)) {
            $date = Time::parse($date);
        }

        $date->setTimezone(setting('App.appTimezone'));

        return $date->format($format);
    }
}

if (! function_exists('render')) {
    /**
     * Renders a view using the current theme.
     *
     * @return string
     */
    function render(string $theme, string $view, array $data = [], array $options = [])
    {
        helper('assets');

        Theme::setTheme($theme);
        $path = Theme::path();

        $renderer = single_service('renderer', $path);

        $viewMeta         = service('viewMeta');
        $data['viewMeta'] = $viewMeta;

        return $renderer->setData($data)
            ->render($view, $options, true);
    }
}

if (! function_exists('site_offline')) {
    /**
     * Determines whether the site is offline.
     */
    function site_offline(): bool
    {
        return empty(setting('Site.siteOnline'));
    }
}

if (! function_exists('admin_accent')) {
    /**
     * The admin theme's accent color as a #rrggbb hex string,
     * falling back to the default when the stored value isn't one.
     */
    function admin_accent(): string
    {
        $accent = setting('Site.adminAccent');

        return is_string($accent) && preg_match('/^#[0-9a-fA-F]{6}$/', $accent) === 1
            ? $accent
            : '#D9622B';
    }
}

if (! function_exists('content')) {
    /**
     * The entry point of the content query API.
     */
    function content(): Content
    {
        static $checkedForChanges = false;

        $contentPath = config('Content')->contentPath;

        // An edited content file shows up on the next request without a reindex.
        if (ENVIRONMENT === 'development' && ! $checkedForChanges) {
            $checkedForChanges = true;
            (new StaleCheck($contentPath))->run();
        }

        return new Content($contentPath);
    }
}

if (! function_exists('content_seo')) {
    /**
     * Adds an entry's SEO values to the page's head through the `viewMeta` service: the title, the description,
     * the canonical link and the Open Graph tags. The layout has to render the `title`, `meta` and `link` types.
     * Values are escaped here, as the service prints what it is given.
     */
    function content_seo(Entry $entry): void
    {
        $seo  = $entry->seo();
        $meta = service('viewMeta');

        $meta->setTitle(esc($seo['title']));

        // viewMeta escapes attribute values itself, so these go in as they are.
        if ($seo['description'] !== '') {
            $meta->addMeta(['name' => 'description', 'content' => $seo['description']]);
        }

        if ($seo['canonical'] !== null) {
            $meta->addLink(['rel' => 'canonical', 'href' => $seo['canonical']]);
        }

        foreach ($seo['og'] as $property => $content) {
            $meta->addMeta(['property' => $property, 'content' => $content]);
        }
    }
}

if (! function_exists('content_markdown')) {
    /**
     * Renders a Markdown string as HTML the way `$entry->content()` does: GitHub Flavored Markdown, then any
     * `<x-…>` View Components expanded. Pass `components: false` to leave them as written. Use it for front matter
     * and block fields that hold Markdown. Raw HTML passes through, so only render text you trust.
     *
     * It isn't called `markdown()` because Postal already defines a global of that name.
     *
     * @throws RuntimeException when a component doesn't exist, except in production, where it is logged and removed
     */
    function content_markdown(string $markdown, bool $components = true): string
    {
        return Markdown::toHtml($markdown, $components);
    }
}

if (! function_exists('resource_routes')) {
    /**
     * Declares the admin routes of a resource: one for each page its `$pages` names.
     * Call it from the module's Config/Routes.php. The routes are named `{slug}-list` and so on.
     *
     * @param class-string<Bonfire\Resources\Resource> $resource
     */
    function resource_routes(RouteCollection $routes, string $resource): void
    {
        $resource = new $resource();

        $routes->group(ADMIN_AREA, static function (RouteCollection $routes) use ($resource): void {
            if ($resource->allows('list')) {
                $routes->get($resource->slug, [[ResourceController::class, 'list'], $resource->slug], ['as' => $resource->slug . '-list']);
            }
        });
    }
}

if (! function_exists('globals')) {
    /**
     * A set of site-wide values from content/globals, read straight from its file.
     *
     * @throws ContentException when the set doesn't exist or its file is invalid
     */
    function globals(string $handle): GlobalSet
    {
        return GlobalSet::load(config('Content')->contentPath, config('Content')->blueprintPath, $handle);
    }
}

if (! function_exists('navigation')) {
    /**
     * A menu built in the admin, as a tree of items for a site to show.
     *
     * @return list<NavigationItem>
     */
    function navigation(string $handle): array
    {
        return content()->navigation($handle);
    }
}
