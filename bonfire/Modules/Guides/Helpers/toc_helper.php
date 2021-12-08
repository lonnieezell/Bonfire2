<?php

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
     *
     * @return mixed
     */
    function load_recursive_guide($alias, $page, $folder)
    {
        // Name of folder
        $string = '<h3>' . esc(ucwords(trim(Bonfire\Modules\Guides\Libraries\GuideCollection::formatPage($folder), ' /'))) . '</h3>
					<ul class="list-unstyled px-4">';

        foreach ($page as $sub_folder => $row) {
            if (is_numeric($sub_folder)) {
                // Is file
                $string .= '<li>
								<a href="/' . ADMIN_AREA . '/guides/' . $alias . '-' . trim($folder, ' /') . '/' . trim($row, ' /') . '">
									' . esc(Bonfire\Modules\Guides\Libraries\GuideCollection::formatPage($row)) . '
								</a>
							</li>';
            } else {
                // Is subfolder
                $string = load_recursive_guide($alias . '-' . trim($folder, ' /'), $row, trim($sub_folder, ' /')) . $string;
            }
        }

        return $string . '</ul>';
    }
}
