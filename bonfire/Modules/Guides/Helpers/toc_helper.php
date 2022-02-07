<?php

use Bonfire\Modules\Guides\Libraries\GuideCollection;

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */
if (! function_exists('load_recursive_guide')) {
    /**
     * Generates a list of guides when recursively search all subfolders
     *
     * @param mixed $alias
     * @param mixed $page
     * @param mixed $folder
     * @param bool  $inSubFolder
     *
     * @return mixed
     */
    function load_recursive_guide($alias, $page, $folder, bool $inSubFolder=false)
    {
        asort($page);

        // Name of folder
        $string = esc(ucwords(trim(GuideCollection::formatPage($folder), ' /')));
        $string = $inSubFolder
            ? '<h5>'. $string .'</h5>'
            : '<h3>'. $string .'</h3>';
        $string .=	'<ul class="list-unstyled px-4">';

        foreach ($page as $sub_folder => $row) {
            if (is_numeric($sub_folder)) {
                // Is file
                $string .= '<li>
								<a href="/' . ADMIN_AREA . '/guides/' . $alias . '-' . trim($folder, ' /') . '/' . trim($row, ' /') . '">
									' . esc(GuideCollection::formatPage($row)) . '
								</a>
							</li>';
            } else {
                // Is subfolder
                $string .= load_recursive_guide($alias . '-' . trim($folder, ' /'), $row, trim($sub_folder, ' /'), true);
            }
        }

        return $string . '</ul>';
    }
}
