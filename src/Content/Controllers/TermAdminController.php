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

use Bonfire\Content\Blueprint;
use Bonfire\Content\ContentException;
use Bonfire\Content\EntryForm;
use Bonfire\Content\Taxonomy;
use Bonfire\Content\TermStore;
use Bonfire\Core\AdminController;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\Exceptions\RedirectException;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * A taxonomy's terms in the admin: a list, and forms to write them from the taxonomy's term blueprint.
 * Terms are part of the structure of the content, so every screen needs content.structure.
 */
class TermAdminController extends AdminController
{
    protected $theme      = 'Admin';
    protected $viewPrefix = 'Bonfire\Content\Views\\';

    /**
     * Turns away anyone without content.structure.
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        if (! auth()->user()->can('content.structure')) {
            throw new RedirectException(redirect()->to(ADMIN_AREA)->with('error', lang('Bonfire.notAuthorized')));
        }
    }

    /**
     * Lists a taxonomy's terms from the index.
     */
    public function list(string $handle): string
    {
        $taxonomy = $this->taxonomy($handle);

        return $this->render($this->viewPrefix . 'term_list', [
            'taxonomy' => $taxonomy,
            'terms'    => content()->terms($taxonomy->handle),
        ]);
    }

    /**
     * The form for a new term.
     *
     * @return RedirectResponse|string
     */
    public function create(string $handle)
    {
        $taxonomy = $this->taxonomy($handle);

        try {
            $form = new EntryForm($this->blueprint($taxonomy), true);
        } catch (ContentException $e) {
            return $this->failed($taxonomy, $e);
        }

        return $this->renderForm($taxonomy, $form, ['values' => $form->emptyValues()]);
    }

    /**
     * Saves a new term.
     *
     * @return RedirectResponse|string
     */
    public function store(string $handle)
    {
        $taxonomy = $this->taxonomy($handle);

        try {
            $form = new EntryForm($this->blueprint($taxonomy), true);
        } catch (ContentException $e) {
            return $this->failed($taxonomy, $e);
        }

        $values = $form->prepare($this->request->getPost());
        $errors = $form->validate($values);
        $shown  = ['values' => $values, 'errors' => $errors];

        if ($errors !== []) {
            return $this->renderForm($taxonomy, $form, $shown);
        }

        $slug = $form->slugOf($values);

        // The slug names the file, and a title of only symbols, or none, makes none.
        if ($slug === '') {
            return $this->renderForm($taxonomy, $form, $shown + ['notice' => lang('Content.termNoSlug')]);
        }

        [$data] = $form->apply($values, [], '');

        try {
            (new TermStore(config('Content')->contentPath))->create($taxonomy, $slug, $data);
        } catch (ContentException $e) {
            return $this->renderForm($taxonomy, $form, $shown + ['notice' => $e->getMessage()]);
        }

        return $this->saved($taxonomy, $slug);
    }

    /**
     * The form for a term that exists.
     *
     * @return RedirectResponse|string
     */
    public function edit(string $handle, string $slug)
    {
        $taxonomy = $this->taxonomy($handle);

        try {
            [$current, $form] = $this->load($taxonomy, $slug);
        } catch (ContentException $e) {
            return $this->failed($taxonomy, $e);
        }

        return $this->renderForm($taxonomy, $form, ['term' => $current, 'values' => $form->valuesOf($this->dataOf($current), '', $slug)]);
    }

    /**
     * Saves a term that exists.
     *
     * @return RedirectResponse|string
     */
    public function update(string $handle, string $slug)
    {
        $taxonomy = $this->taxonomy($handle);

        try {
            [$current, $form] = $this->load($taxonomy, $slug);
        } catch (ContentException $e) {
            return $this->failed($taxonomy, $e);
        }

        $data   = $this->dataOf($current);
        $values = $form->prepare($this->request->getPost());
        $errors = $form->validate($values);

        // What the form can't change is shown as it is.
        $shown = ['term' => $current, 'values' => $values + $form->valuesOf($data, '', $slug), 'errors' => $errors];

        if ($errors !== []) {
            return $this->renderForm($taxonomy, $form, $shown);
        }

        [$data] = $form->apply($values, $data, '');

        try {
            (new TermStore(config('Content')->contentPath))->update($taxonomy, $current, $data);
        } catch (ContentException $e) {
            return $this->renderForm($taxonomy, $form, $shown + ['notice' => $e->getMessage()]);
        }

        return $this->saved($taxonomy, $slug);
    }

    /**
     * @throws PageNotFoundException
     */
    private function taxonomy(string $handle): Taxonomy
    {
        try {
            return Taxonomy::load(config('Content')->contentPath, $handle);
        } catch (ContentException) {
            throw PageNotFoundException::forPageNotFound();
        }
    }

    /**
     * @throws ContentException when the blueprint can't be loaded
     */
    private function blueprint(Taxonomy $taxonomy): Blueprint
    {
        return Blueprint::loadTerm(config('Content')->blueprintPath, $taxonomy->handle);
    }

    /**
     * A term as its file holds it, and the form for it.
     *
     * @return array{array<string, int|string>, EntryForm}
     *
     * @throws ContentException
     * @throws PageNotFoundException when the taxonomy has no such term
     */
    private function load(Taxonomy $taxonomy, string $slug): array
    {
        $current = (new TermStore(config('Content')->contentPath))->read($taxonomy, $slug);

        if ($current === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        return [$current, new EntryForm($this->blueprint($taxonomy), false)];
    }

    /**
     * @param array<string, int|string> $term the term's index columns
     *
     * @return array<array-key, mixed> the keys of its file
     */
    private function dataOf(array $term): array
    {
        return json_decode((string) $term['data'], true, flags: JSON_THROW_ON_ERROR);
    }

    private function saved(Taxonomy $taxonomy, string $slug): RedirectResponse
    {
        return redirect()->to(route_to('content-term-edit', $taxonomy->handle, $slug))->with('message', lang('Bonfire.resourceSaved', [lang('Content.term')]));
    }

    private function failed(Taxonomy $taxonomy, ContentException $e): RedirectResponse
    {
        return redirect()->to(route_to('content-terms', $taxonomy->handle))->with('error', $e->getMessage());
    }

    /**
     * @param array{values: array<string, string>, errors?: array<string, string>, term?: array<string, int|string>, notice?: string} $shown
     */
    private function renderForm(Taxonomy $taxonomy, EntryForm $form, array $shown): string
    {
        $errors = $shown['errors'] ?? [];

        return $this->render($this->viewPrefix . 'term_form', [
            'taxonomy' => $taxonomy,
            'term'     => $shown['term'] ?? null,
            'rows'     => $form->rows($shown['values'], $errors),
            'notice'   => $shown['notice'] ?? null,
        ]);
    }
}
