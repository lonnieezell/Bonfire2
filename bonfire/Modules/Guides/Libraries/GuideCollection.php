<?php

/**
 * This file is part of CodeIgniter 4 framework.
 *
 * (c) CodeIgniter Foundation <admin@codeigniter.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Modules\Guides\Libraries;

use Bonfire\Guides\Exceptions\GuideException;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Extension\CommonMark\Node\Block\IndentedCode;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\MarkdownConverter;
use Spatie\CommonMarkHighlighter\FencedCodeRenderer;
use Spatie\CommonMarkHighlighter\IndentedCodeRenderer;

/**
 * Manages a single collection of guides.
 */
class GuideCollection
{
    /**
     * @var array
     */
    protected $settings;

    /**
     * @var string
     */
    protected $alias;

    public function __construct(string $alias, array $settings)
    {
        $this->alias    = $alias;
        $this->settings = $settings;
    }

    /**
     * Returns a link to the Collection TOC
     *
     * @return string
     */
    public function link()
    {
        return site_url(ADMIN_AREA . '/guides/' . $this->alias);
    }

    /**
     * Simple sanity checks to make sure this
     * collection has the required bits to
     * make a complete collection.
     */
    public function isValid(): bool
    {
        return true;
    }

    /**
     * Returns the collection title.
     */
    public function title(): string
    {
        return $this->settings['title'] ?? '';
    }

    /**
     * Generates an HTML representation of the
     * guide collection pages.
     */
    public function tableOfContents(): string
    {
        helper('filesystem');

        $pages = $this->readDir(ROOTPATH . $this->settings['path']);

        // Ensure guide numbers are sorted correctly
        asort($pages);

        return view('\Bonfire\Modules\Guides\Views\_toc', [
            'pages' => $pages,
            'alias' => $this->alias,
        ]);
    }

    /**
     * Formats page names for use in TOC
     *
     * @return string
     */
    public static function formatPage(string $page)
    {
        // Strip any preceeding numbers that are used for ordering
        $page = preg_replace('|^[0-9].|', '', $page);

        return ltrim(
            ucfirst(
                str_replace(
                    '.md',
                    '',
                    str_replace(['-', '_'], ' ', $page)
                )
            ),
            '/'
        );
    }

    /**
     * Reads and converts a single page.
     */
    public function loadPage(string $path): string
    {
        $file = ROOTPATH . $this->settings['path'] . '/' . $path;

        if (! is_file($file)) {
            throw GuideException::forInvalidPage();
        }

        $out = file_get_contents($file);

        $env = new Environment();
        $env->addExtension(new CommonMarkCoreExtension());
        $env->addExtension(new GithubFlavoredMarkdownExtension());
        $env->addRenderer(FencedCode::class, new FencedCodeRenderer(['html', 'php', 'js']));
        $env->addRenderer(IndentedCode::class, new IndentedCodeRenderer());

        $markdown = new MarkdownConverter($env);

        return $markdown->convertToHtml($out);
    }

    /**
     * Displays the "pagination" links for the current page
     *
     * @return string
     */
    public function pageLinks()
    {
        helper('filesystem');

        $offset      = strlen('guides/' . $this->alias) + 1;
        $currentPage = current_url();
        $currentPage = substr($currentPage, strpos($currentPage, 'guides/' . $this->alias) + $offset);

        $previous = $this->nextPrevGenerator($currentPage, -1);
        $next     = $this->nextPrevGenerator($currentPage, +1);

        return view('\Bonfire\Modules\Guides\Views\_page_links', [
            'previousTitle' => $previous !== null ? self::formatPage($previous) : null,
            'previousLink'  => ! empty($previous) ? site_url(ADMIN_AREA . '/guides/' . $this->alias . $previous) : null,
            'nextTitle'     => $next !== null ? self::formatpage($next) : null,
            'nextLink'      => ! empty($next) ? site_url(ADMIN_AREA . '/guides/' . $this->alias . $next) : null,
        ]);
    }

    /**
     * Calculate the next and previous links
     *
     *  currentPage	-> Current Page
     *  $pos		-> next = +1 / previous = -1
     */
    private function nextPrevGenerator(string $currentPage, int $pos): ?string
    {
        $rootPath = ROOTPATH . $this->settings['path'] . DIRECTORY_SEPARATOR;

        $page  = $rootPath . $currentPage;
        $files = $this->find_all_files(ROOTPATH . $this->settings['path']);

        $currentIdx = array_search($page, $files, true);

        $Idx = $currentIdx + $pos;
        if (isset($files[$Idx])) {
            $posFile  = $files[$Idx];
            $pathFile = pathinfo($posFile, PATHINFO_DIRNAME);

            if ($pathFile !== rtrim($rootPath, '/')) {
                $urlPath = '-' . str_replace('/', '-', str_replace($rootPath, '', $pathFile));
            } else {
                $urlPath = '';
            }
            $file = pathinfo($posFile, PATHINFO_FILENAME) . '.' . pathinfo($posFile, PATHINFO_EXTENSION);

            return $urlPath . '/' . $file;
        }
        if ($pos > 0) {
            // echo "No next file";
            return null;
        }
        // echo "No previous file";
        return null;
    }

    /**
     * Recursive function to retrieve a list of files
     * in the given path.
     */
    public function find_all_files(string $path): array
    {
        $result = [];
        if (is_dir($path)) {
            $root = scandir($path);

            foreach ($root as $value) {
                if ($value === '.' || $value === '..') {
                    continue;
                }
                if (is_file("{$path}/{$value}")) {
                    $result[] = "{$path}/{$value}";

                    continue;
                }

                foreach ($this->find_all_files("{$path}/{$value}") as $value) {
                    $result[] = $value;
                }
            }
            sort($result);
        }

        return $result;
    }

    /**
     * Recursive function to read all of the files
     * in the given path.
     *
     * @param array $pages
     *
     * @return array|mixed
     */
    private function readDir(string $path, $pages = [])
    {
        $files = directory_map($path, 2);

        foreach ($files as $folder => $file) {
            // Handle folders of pages
            if (is_array($file)) {
                $pages[$folder] = $this->readDir(rtrim($path, '/') . '/' . $folder);

                continue;
            }

            // Handle single page
            $pages[] = $file;
        }

        return $pages;
    }
}
