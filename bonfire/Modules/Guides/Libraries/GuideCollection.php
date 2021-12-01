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

		return ltrim (
			ucfirst(
				str_replace('.md', '',
					str_replace(['-', '_'], ' ', $page)
				)
			)
			, "/");
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

		$previous = $this->nextPrevGenerator($currentPage, $pages, -1);
		$next = $this->nextPrevGenerator($currentPage, $pages, +1);

		return view('\Bonfire\Modules\Guides\Views\_page_links', [
			'previousTitle' => $previous !== null ? self::formatPage($previous) : null,
			'previousLink' => ! empty($previous) ? site_url(ADMIN_AREA .'/guides/'. $this->alias . $previous) : null,
			'nextTitle' => $next !== null ? self::formatpage($next) : null,
			'nextLink' => ! empty($next) ? site_url(ADMIN_AREA .'/guides/'. $this->alias . $next) : null,
		]);
	}

	/**
	 * Calculate the next and previous links
	 *
	 *  currentPage	-> Current Page
	 *  pages		-> Pages list from folder
	 *  $pos		-> next = +1 / previous = -1
	 *
	 * @param string $currentPage
	 * @param array $pages
	 * @param int $pos
	 * @return string|null
	 */
	private function nextPrevGenerator(string $currentPage, array $pages, int $pos): ?string
	{
		$result = null;

		//sort the pages
		$sortPages = $pages;
		sort($sortPages);

		$testCurrentPage = explode("/", $currentPage)[array_key_last(explode("/", $currentPage))];

		//I loop through the sorted pages
		for($i=0; $i < count($sortPages); $i++) {

			//If I find the current page
			if ($sortPages[$i] === $testCurrentPage) {

				//I calculate the position
				$nextPrevPos = $i+$pos;
				if(!isset($sortPages[$nextPrevPos])) {
					//If it is the first or last file, it returns a null link
					$result = null;
					break;
				}else {
					//If it's not an array, it's not a folder
					if (! is_array($sortPages[$nextPrevPos])) {
						$result = $sortPages[$nextPrevPos] ? '/' . $sortPages[$nextPrevPos] : null;

						break;
					}else{
						foreach ($pages as $folder => $content)
						{
							if(is_string($folder)) {
								if($sortPages[$nextPrevPos] === $pages[$folder] ) {
									$result = "-" . rtrim($folder, "/") . $this->nextPrevGenerator($folder . $sortPages[$nextPrevPos][0], $sortPages[$nextPrevPos], 0);
									break;
								}else{
									//TODO:
									//$result = null;
								}
							}
						}
					}
					break;
				}

			}else{

				//I calculate the position
				$nextPrevPos = $i+$pos;
				if(! isset($sortPages[$nextPrevPos]) ) {
					foreach ($pages as $folder => $content)
					{
						if(is_string($folder)) {
							if($folder === explode("/" , $currentPage)[0] . "/" ) {
								if($this->nextPrevGenerator($currentPage, $pages[$folder], $pos)) {
									$result = "-" . rtrim($folder, "/") . $this->nextPrevGenerator($currentPage, $pages[$folder], $pos);
								}
							}else{

								foreach ($pages as $current_sub_folder => $sub_content)
								{
									if(is_string($current_sub_folder)) {
										if(isset(explode("/", $currentPage)[1])) {
											$currentFolder    = explode("/", $currentPage)[0] ;
											$currentPage      = explode("/", $currentPage)[1] ;
											$sortCurrentSubPages =  $pages[$currentFolder . "/"];
											sort($sortCurrentSubPages);
											$result = "-" . $currentFolder . $this->nextPrevGenerator( $currentPage, $sortCurrentSubPages, $pos);
											break;
										}
									}
								}
							}
						}
					}
				}else{
					foreach ($pages as $current_sub_folder => $sub_content)
					{
						if(is_string($current_sub_folder)) {
							if(isset(explode("/", $currentPage)[1])) {
								$currentFolder    = explode("/", $currentPage)[0] ;
								$currentPage      = explode("/", $currentPage)[1] ;
								$sortCurrentSubPages =  $pages[$currentFolder . "/"];
								sort($sortCurrentSubPages);
								$result = "-" . $currentFolder . $this->nextPrevGenerator( $currentPage, $sortCurrentSubPages, $pos);
								break;
							}
						}
					}
				}
			}

		}

		return $result;

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
				$pages[$folder] = $this->readDir(rtrim($path, "/") .'/'. $folder);
				continue;
			}

			// Handle single page
			$pages[] = $file;
		}

		return $pages;
	}

}
