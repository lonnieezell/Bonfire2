<?php

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
		$this->alias = $alias;
		$this->settings = $settings;
	}

	/**
	 * Returns a link to the Collection TOC
	 *
	 * @return string
	 */
	public function link()
	{
		return site_url(ADMIN_AREA .'/guides/'. $this->alias);
	}

	/**
	 * Simple sanity checks to make sure this
	 * collection has the required bits to
	 * make a complete collection.
	 *
	 * @return bool
	 */
	public function isValid(): bool
	{
		return true;
	}

	/**
	 * Returns the collection title.
	 *
	 * @return string
	 */
	public function title(): string
	{
		return $this->settings['title'] ?? '';
	}

	/**
	 * Generates an HTML representation of the
	 * guide collection pages.
	 *
	 * @return string
	 */
	public function tableOfContents(): string
	{
		helper('filesystem');

		$pages = $this->readDir(ROOTPATH .$this->settings['path']);

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
	 * @param string $page
	 *
	 * @return string
	 */
	public static function formatPage(string $page)
	{
		// Strip any preceeding numbers that are used for ordering
		$page = preg_replace('|^[0-9].|', '', $page);

		return ucfirst(
			str_replace('.md', '',
				str_replace(['-', '_'], ' ', $page)
			)
		);
	}

	/**
	 * Reads and converts a single page.
	 *
	 * @param string $path
	 */
	public function loadPage(string $path): string
	{
		$file = ROOTPATH . $this->settings['path'] .'/'. $path;

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
		$out = $markdown->convertToHtml($out);

		return $out;
	}

	/**
	 * Displays the "pagination" links for the current page
	 *
	 * @return string
	 */
	public function pageLinks()
	{
		helper('filesystem');

		$pages = $this->readDir(ROOTPATH .$this->settings['path']);

		$offset = strlen('guides/'. $this->alias) +1;
		$currentPage = current_url();
		$currentPage = substr($currentPage, strpos($currentPage, 'guides/'. $this->alias) + $offset);

		$previous = null;
		$next = null;
		for($i=0; $i < count($pages) -1; $i++) {
			if ($pages[$i] == $currentPage) {
				$previous = $pages[$i-1] ?? null;
				$next = $pages[$i+1] ?? null;
				break;
			}
		}

		return view('\Bonfire\Modules\Guides\Views\_page_links', [
			'previousTitle' => $previous !== null ? self::formatPage($previous) : null,
			'previousLink' => ! empty($previous) ? site_url(ADMIN_AREA .'/guides/'. $this->alias .'/'. $previous) : null,
			'nextTitle' => $next !== null ? self::formatpage($next) : null,
			'nextLink' => ! empty($next) ? site_url(ADMIN_AREA .'/guides/'. $this->alias .'/'. $next) : null,
		]);
	}

	/**
	 * Recursive function to read all of the files
	 * in the given path.
	 *
	 * @param string $path
	 * @param array  $pages
	 *
	 * @return array|mixed
	 */
	private function readDir(string $path, $pages=[])
	{
		$files = directory_map($path, 2);

		foreach ($files as $folder => $file) {
			// Handle folders of pages
			if(is_array($file)) {
				$pages[$folder] = $this->readDir($path .'/'. $folder);
				continue;
			}

			// Handle single page
			$pages[] = $file;
		}

		return $pages;
	}
}
