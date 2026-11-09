<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Content\Controllers;

use Bonfire\Content\ContentException;
use Bonfire\Content\Fields\BlocksField;
use Bonfire\Content\Fields\ListField;
use Bonfire\Core\AdminController;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\Exceptions\RedirectException;

/**
 * Draws the new rows of list fields. It's apart from EntryAdminController because a list can be on a term's form, which
 * content.structure opens without the content.access that controller needs.
 */
class ListRowController extends AdminController
{
    /**
     * The card of a new row, with the inputs of a list field's sub-fields. The field's Add row button fetches it with
     * htmx. The field comes from a signed token, because the row's owner may be any blueprint or block type. It's posted
     * because a large definition would be too long for a URL. `name` and `id` say where in the form the list is, which
     * a duplicated block changes. A list that has as many rows as it may gets no row, going by `count`. Saving checks it.
     */
    public function row(): string
    {
        $user = auth()->user();

        if (! $user->can('content.structure') && ! ($user->can('content.access') && $user->can('content.edit'))) {
            throw new RedirectException(redirect()->to(ADMIN_AREA)->with('error', lang('Bonfire.notAuthorized')));
        }

        $field = ListField::fromToken($this->request->getPost('field'));
        $name  = $this->request->getPost('name');
        $id    = $this->request->getPost('id');

        if (
            $field === null
            || ! is_string($name) || preg_match('/^fields(\[[A-Za-z0-9_-]+\])+$/', $name) !== 1
            || ! is_string($id) || preg_match('/^field[A-Za-z0-9_-]*$/', $id) !== 1
        ) {
            throw PageNotFoundException::forPageNotFound();
        }

        $max = BlocksField::bound($field, 'max_items');

        if ($max !== null && (int) $this->request->getPost('count') >= $max) {
            return $this->rotatedCsrfInput();
        }

        try {
            $blueprint = ListField::rowBlueprint($field);
        } catch (ContentException) {
            throw PageNotFoundException::forPageNotFound();
        }

        return view('Bonfire\Content\Views\block_card', [
            'name'       => $name,
            'id'         => $id,
            'key'        => BlocksField::newKey(),
            'block'      => BlocksField::valuesFrom($blueprint->fields, []),
            'blueprint'  => $blueprint,
            'cardErrors' => [],
            'position'   => 0,
            'open'       => true,
            'slot'       => false,
            'disabled'   => false,
            'isRow'      => true,
        ], ['saveData' => false]) . $this->rotatedCsrfInput();
    }

    /**
     * Verifying a post rotates the CSRF token, so htmx swaps this over the page's token inputs out of band. Without it,
     * the next post from the page would be refused.
     */
    private function rotatedCsrfInput(): string
    {
        $name = csrf_token();

        return '<input type="hidden" name="' . esc($name, 'attr') . '" value="' . esc(csrf_hash(), 'attr') . '" hx-swap-oob="' . esc("outerHTML:input[type=hidden][name=\"{$name}\"]", 'attr') . '">';
    }
}
