<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Fields\Controllers;

use Bonfire\Core\BaseController;
use Bonfire\Fields\OptionsToken;
use Bonfire\Fields\Types\BelongsToField;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * The records a belongsTo typeahead offers for what was typed. It reads only what the signed token names, and only
 * for a user with `admin.access` and the permission the token carries.
 */
class OptionsController extends BaseController
{
    public function index(): ResponseInterface|string
    {
        $user  = auth()->user();
        $token = OptionsToken::read($this->request->getGet('field'));

        if ($token === null || $user === null || ! $user->can('admin.access') || ($token['permission'] !== null && ! $user->can($token['permission']))) {
            return $this->response->setStatusCode(403)->setBody('');
        }

        $text = $this->request->getGet('q');

        return view('Bonfire\Fields\Views\options', [
            'records' => BelongsToField::search($token['model'], $token['title'], $token['search'], is_string($text) ? trim($text) : ''),
        ], ['saveData' => false]);
    }
}
