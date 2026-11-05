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
use Bonfire\Content\Collection;
use Bonfire\Content\ContentException;
use Bonfire\Content\Entry;
use Bonfire\Content\EntryForm;
use Bonfire\Content\EntryStore;
use Bonfire\Content\Fields\DateField;
use Bonfire\Content\Models\ContentEntryModel;
use Bonfire\Content\SaveConflictException;
use Bonfire\Content\Structure;
use Bonfire\Core\AdminController;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\Exceptions\RedirectException;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use DateTimeImmutable;
use League\CommonMark\GithubFlavoredMarkdownConverter;
use Psr\Log\LoggerInterface;

/**
 * Content in the admin: a list of each collection's entries, forms to write them from their blueprints, and deleting them.
 */
class EntryAdminController extends AdminController
{
    use LoadsSortable;

    protected $theme      = 'Admin';
    protected $viewPrefix = 'Bonfire\Content\Views\\';

    /**
     * Turns away anyone without content.access.
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        if (! auth()->user()->can('content.access')) {
            $this->deny();
        }
    }

    /**
     * Lists a collection's entries from the index.
     */
    public function list(string $handle): string
    {
        $collection = $this->collection($handle);
        $query      = content()->collection($collection->handle);
        $entries    = $query->paginate(setting('Site.perPage'));
        $canDelete  = auth()->user()->can('content.delete');

        return $this->render($this->viewPrefix . 'entry_list', [
            'collection' => $collection,
            'entries'    => $entries,
            'pager'      => $query->pager(),
            'canEdit'    => auth()->user()->can('content.edit'),
            'canDelete'  => $canDelete,
            'below'      => $canDelete ? $this->entryStore()->descendantCounts($collection, array_map(static fn (Entry $entry): string => $entry->id, $entries)) : [],
            'canArrange' => $collection->structure && auth()->user()->can('content.structure'),
        ]);
    }

    /**
     * The tree editor of a structured collection.
     */
    public function structure(string $handle): string
    {
        $this->requireStructure();

        $collection             = $this->structured($handle);
        [$tree, $hash, $notice] = $this->readTree($collection);

        return $this->renderTree($collection, $tree, $hash, $notice);
    }

    /**
     * Saves the posted tree, unless the tree file has changed since the editor was opened.
     *
     * @return RedirectResponse|string
     */
    public function saveStructure(string $handle)
    {
        $this->requireStructure();

        $collection = $this->structured($handle);
        $post       = $this->request->getPost();
        $hash       = is_string($post['hash'] ?? null) ? $post['hash'] : '';
        $tree       = null;

        try {
            $tree = Structure::fromJson(is_string($post['tree'] ?? null) ? $post['tree'] : '');
            $this->entryStore()->saveTree($collection, $tree, $hash);
        } catch (SaveConflictException) {
            return $this->renderTree($collection, $tree ?? $this->readTree($collection)[0], $hash, lang('Content.structureConflict'), true, 409);
        } catch (ContentException $e) {
            return $this->renderTree($collection, $tree ?? $this->readTree($collection)[0], $hash, $e->getMessage(), false, 422);
        }

        return redirect()->to(route_to('content-structure', $collection->handle))->with('message', lang('Bonfire.resourceSaved', [lang('Content.structure')]));
    }

    /**
     * The form for a new entry.
     *
     * @return RedirectResponse|string
     */
    public function create(string $handle)
    {
        $this->requireEdit();

        $collection = $this->collection($handle);

        try {
            $blueprint = $this->blueprintFor($collection, $this->request->getGet('blueprint'));
        } catch (ContentException $e) {
            return $this->failed($collection, $e);
        }

        $form = new EntryForm($blueprint, true);

        return $this->renderForm($collection, $blueprint, $form, [
            'values' => $form->emptyValues(),
            'status' => 'draft',
        ]);
    }

    /**
     * Saves a new entry.
     *
     * @return RedirectResponse|string
     */
    public function store(string $handle)
    {
        $this->requireEdit();

        $collection = $this->collection($handle);

        try {
            $blueprint = $this->blueprintFor($collection, $this->request->getPost('blueprint'));
        } catch (ContentException $e) {
            return $this->failed($collection, $e);
        }

        $form = new EntryForm($blueprint, true);

        [$values, $status, $publishedAt, $errors, $denied] = $this->check($form, $this->request->getPost(), 'draft', null);

        $shown = ['values' => $values, 'status' => $status, 'publishedAt' => $publishedAt ?? '', 'errors' => $errors];

        if ($errors !== []) {
            return $this->renderForm($collection, $blueprint, $form, $shown, $denied ? 403 : 200);
        }

        $slug = $form->slugOf($values);

        // The slug names the file, and a title of only symbols, or none, makes none.
        if ($slug === '') {
            return $this->renderForm($collection, $blueprint, $form, $shown + ['notice' => lang('Content.noSlug')]);
        }

        [$frontMatter, $body] = $form->apply($values, [], '');
        $frontMatter          = $this->withPublishing($frontMatter, $status, $publishedAt, null);

        if ($blueprint->handle !== ($collection->blueprints[0] ?? null)) {
            $frontMatter['blueprint'] = $blueprint->handle;
        }

        try {
            $record = $this->entryStore()->create($collection, $slug, $frontMatter, $body, $publishedAt);
        } catch (ContentException $e) {
            return $this->renderForm($collection, $blueprint, $form, $shown + ['notice' => $e->getMessage()]);
        }

        return $this->saved($collection, (string) $record['id']);
    }

    /**
     * The form for an entry that exists.
     *
     * @return RedirectResponse|string
     */
    public function edit(string $handle, string $id)
    {
        $this->requireEdit();

        $collection = $this->collection($handle);

        try {
            [$current, $entry, $blueprint] = $this->load($collection, $id);
        } catch (ContentException $e) {
            return $this->failed($collection, $e);
        }

        $form = new EntryForm($blueprint, false);

        return $this->renderForm($collection, $blueprint, $form, [
            'entry'       => $entry,
            'hash'        => (string) $current['hash'],
            'values'      => $form->valuesOf($entry->data, $entry->body, $entry->slug),
            'status'      => $entry->status,
            'publishedAt' => $entry->publishedAt ?? '',
        ]);
    }

    /**
     * Saves an entry that exists, unless its file has changed since the form was opened.
     *
     * @return RedirectResponse|string
     */
    public function update(string $handle, string $id)
    {
        $this->requireEdit();

        $collection = $this->collection($handle);

        try {
            // A form opened before the entry was trashed gets the conflict, not a 404.
            [$current, $entry, $blueprint] = $this->load($collection, $id, true);
        } catch (ContentException $e) {
            return $this->failed($collection, $e);
        }

        $post = $this->request->getPost();
        $form = new EntryForm($blueprint, false);

        [$values, $status, $publishedAt, $errors, $denied] = $this->check($form, $post, $entry->status, $entry->publishedAt);

        // Without a publish date in the front matter, a dated entry takes the one in its file name, so it can't be cleared.
        if ($collection->dated && $publishedAt === null) {
            $errors['published_at'] = lang('Content.datedNeedsDate');
        }

        $hash  = is_string($post['hash'] ?? null) ? $post['hash'] : '';
        $shown = ['entry' => $entry, 'hash' => $hash, 'values' => $values, 'status' => $status, 'publishedAt' => $publishedAt ?? '', 'errors' => $errors];

        // What the form can't change is shown as it is.
        $shown['values'] += $form->valuesOf($entry->data, $entry->body, $entry->slug);

        // Fixing what's invalid wouldn't help, because the entry went to the trash after the form was opened.
        if (($current['deleted_at'] ?? null) !== null) {
            return $this->renderForm($collection, $blueprint, $form, $shown + ['notice' => lang('Content.conflict'), 'conflict' => true, 'trashed' => true], 409);
        }

        if ($errors !== []) {
            return $this->renderForm($collection, $blueprint, $form, $shown, $denied ? 403 : 200);
        }

        [$frontMatter, $body] = $form->apply($values, $entry->data, $entry->body);
        $frontMatter          = $this->withPublishing($frontMatter, $status, $publishedAt, $entry->publishedAt);

        try {
            $this->entryStore()->update($collection, $current, $frontMatter, $body, $hash);
        } catch (SaveConflictException) {
            return $this->renderForm($collection, $blueprint, $form, $shown + ['notice' => lang('Content.conflict'), 'conflict' => true], 409);
        } catch (ContentException $e) {
            return $this->renderForm($collection, $blueprint, $form, $shown + ['notice' => $e->getMessage()]);
        }

        return $this->saved($collection, $entry->id);
    }

    /**
     * Moves an entry, and the pages below it, to the trash, unless there are more or fewer of them than the user was told.
     */
    public function delete(string $handle, string $id): RedirectResponse
    {
        if (! auth()->user()->can('content.delete')) {
            $this->deny();
        }

        $collection = $this->collection($handle);

        try {
            $found = $this->entryStore()->trash($collection, $id, (int) $this->request->getPost('descendants'));
        } catch (ContentException $e) {
            return $this->failed($collection, $e);
        }

        if (! $found) {
            throw PageNotFoundException::forPageNotFound();
        }

        return redirect()->to(route_to('content-list', $collection->handle))->with('message', lang('Content.trashed'));
    }

    /**
     * Renders Markdown for the markdown field's Preview tab. Raw HTML in it shows as text, because
     * this is the admin and the HTML is whatever has been typed into the box.
     */
    public function preview(): string
    {
        $this->requireEdit();

        // Only htmx sends this, and a page on another site can't make a browser add it to a form post.
        if ($this->request->getHeaderLine('HX-Request') !== 'true') {
            throw PageNotFoundException::forPageNotFound();
        }

        $markdown = $this->request->getPost('markdown');

        if (! is_string($markdown) || trim($markdown) === '') {
            return '<p class="text-muted">' . esc(lang('Content.previewEmpty')) . '</p>';
        }

        return (new GithubFlavoredMarkdownConverter(['html_input' => 'escape', 'allow_unsafe_links' => false]))->convert($markdown)->getContent();
    }

    /**
     * @throws RedirectException
     */
    private function deny(): never
    {
        throw new RedirectException(redirect()->to(ADMIN_AREA)->with('error', lang('Bonfire.notAuthorized')));
    }

    /**
     * @throws RedirectException when the user can't write content
     */
    private function requireEdit(): void
    {
        if (! auth()->user()->can('content.edit')) {
            $this->deny();
        }
    }

    /**
     * @throws RedirectException when the user can't arrange content
     */
    private function requireStructure(): void
    {
        if (! auth()->user()->can('content.structure')) {
            $this->deny();
        }
    }

    /**
     * @throws PageNotFoundException when the collection has no structure
     */
    private function structured(string $handle): Collection
    {
        $collection = $this->collection($handle);

        if (! $collection->structure) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $collection;
    }

    /**
     * The tree file as it is now. One that can't be read shows as a flat list, with the reason, and saving replaces it.
     *
     * @return array{Structure, string, string|null} the tree, the hash of the file and what is wrong with it
     */
    private function readTree(Collection $collection): array
    {
        $raw = Structure::raw(config('Content')->contentPath, $collection->handle);

        try {
            return [Structure::parse($raw), Structure::hash($raw), null];
        } catch (ContentException $e) {
            return [new Structure([]), Structure::hash($raw), $e->getMessage()];
        }
    }

    private function renderTree(Collection $collection, Structure $tree, string $hash, ?string $notice, bool $conflict = false, int $httpStatus = 200): string
    {
        $entries = array_column((new ContentEntryModel())->withDeleted()->select('id, slug, title, status, deleted_at')->where('collection', $collection->handle)->orderBy('id')->findAll(), null, 'id');

        $this->addSortableScript();

        $this->response->setStatusCode($httpStatus);

        return $this->render($this->viewPrefix . 'entry_tree', [
            'collection' => $collection,
            'nodes'      => $tree->arrange(array_column($entries, 'slug', 'id'))->nodes,
            'entries'    => $entries,
            'trashed'    => count(array_filter(array_column($entries, 'deleted_at'))),
            'hash'       => $hash,
            'notice'     => $notice,
            'conflict'   => $conflict,
        ]);
    }

    /**
     * @throws PageNotFoundException
     */
    private function collection(string $handle): Collection
    {
        try {
            return Collection::load(config('Content')->contentPath, $handle);
        } catch (ContentException) {
            throw PageNotFoundException::forPageNotFound();
        }
    }

    /**
     * The blueprint a new entry uses: the one asked for, or else the collection's first.
     *
     * @throws ContentException when the collection doesn't offer it, or it can't be loaded
     */
    private function blueprintFor(Collection $collection, mixed $requested): Blueprint
    {
        $handle = is_string($requested) && $requested !== '' ? $requested : ($collection->blueprints[0] ?? null);

        if ($handle === null || ! in_array($handle, $collection->blueprints, true)) {
            throw new ContentException(lang('Content.noBlueprint'));
        }

        return Blueprint::load(config('Content')->blueprintPath, $collection->handle, $handle);
    }

    /**
     * An entry as its file holds it, and the blueprint of its form.
     *
     * @return array{array<string, int|string|null>, Entry, Blueprint}
     *
     * @throws ContentException
     * @throws PageNotFoundException when the collection has no such entry
     */
    private function load(Collection $collection, string $id, bool $orTrashed = false): array
    {
        $current = $this->entryStore()->read($collection, $id, $orTrashed);

        if ($current === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        if ($current['blueprint'] === null) {
            throw new ContentException(lang('Content.noBlueprint'));
        }

        return [$current, Entry::fromRow($current), Blueprint::load(config('Content')->blueprintPath, $collection->handle, (string) $current['blueprint'])];
    }

    /**
     * Tidies and validates a post.
     *
     * @param array<array-key, mixed> $post
     *
     * @return array{array<string, string>, string, string|null, array<string, string>, bool} the values, the status, the publish date,
     *                                                                                        the errors and whether the user may not publish
     */
    private function check(EntryForm $form, array $post, string $currentStatus, ?string $currentAt): array
    {
        $values = $form->prepare($post);

        [$status, $publishedAt, $publishErrors, $denied] = $this->publishing($post, $currentStatus, $currentAt);

        return [$values, $status, $publishedAt, $form->validate($values) + $publishErrors, $denied];
    }

    /**
     * The status and publish date to save, whether the posted ones may be used, and the errors with them.
     * A field the form didn't post, such as a disabled one, stays as it is.
     * Publishing, and changing when an entry goes live, needs content.publish.
     *
     * @param array<array-key, mixed> $post
     *
     * @return array{string, string|null, array<string, string>, bool} the status, the publish date, the errors and whether the user may not publish
     */
    private function publishing(array $post, string $currentStatus, ?string $currentAt): array
    {
        $status = is_string($post['status'] ?? null) ? $post['status'] : $currentStatus;
        $at     = $currentAt;
        $errors = [];

        if (is_string($post['published_at'] ?? null)) {
            $posted = DateField::normalize($post['published_at'], true);
            $at     = $posted === '' ? null : $posted;
        }

        if (! in_array($status, ['draft', 'published'], true)) {
            $errors['status'] = lang('Content.invalidStatus');
        }

        // createFromFormat() accepts 2026-02-31 and gives March, so the date must come back as it went in.
        if ($at !== null && (DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $at) ?: null)?->format('Y-m-d H:i:s') !== $at) {
            $errors['published_at'] = lang('Content.invalidPublishedAt');
        }

        $denied = false;

        if ($errors === [] && ($at !== $currentAt || ($status === 'published' && $currentStatus !== 'published')) && ! auth()->user()->can('content.publish')) {
            $errors['status'] = lang('Content.cannotPublish');
            $denied           = true;
        }

        return [$status, $at, $errors, $denied];
    }

    /**
     * @param array<array-key, mixed> $frontMatter
     *
     * @return array<array-key, mixed>
     */
    private function withPublishing(array $frontMatter, string $status, ?string $publishedAt, ?string $currentAt): array
    {
        $frontMatter['status'] = $status;

        if ($publishedAt !== $currentAt) {
            if ($publishedAt === null) {
                unset($frontMatter['published_at']);
            } else {
                $frontMatter['published_at'] = $publishedAt;
            }
        }

        return $frontMatter;
    }

    private function entryStore(): EntryStore
    {
        return new EntryStore(config('Content')->contentPath);
    }

    private function saved(Collection $collection, string $id): RedirectResponse
    {
        return redirect()->to(route_to('content-edit', $collection->handle, $id))->with('message', lang('Bonfire.resourceSaved', [lang('Content.entry')]));
    }

    private function failed(Collection $collection, ContentException $e): RedirectResponse
    {
        // An alert doesn't escape what it shows, and this can name an entry.
        return redirect()->to(route_to('content-list', $collection->handle))->with('error', esc($e->getMessage()));
    }

    /**
     * @param array{values: array<string, string>, status: string, errors?: array<string, string>, entry?: Entry, hash?: string, publishedAt?: string, notice?: string, conflict?: bool, trashed?: bool} $shown
     */
    private function renderForm(Collection $collection, Blueprint $blueprint, EntryForm $form, array $shown, int $httpStatus = 200): string
    {
        $errors = $shown['errors'] ?? [];
        $rows   = $form->rows($shown['values'], $errors);
        $entry  = $shown['entry'] ?? null;

        $canDelete = $entry !== null && ! ($shown['trashed'] ?? false) && auth()->user()->can('content.delete');

        $this->response->setStatusCode($httpStatus);

        return $this->render($this->viewPrefix . 'entry_form', [
            'collection'  => $collection,
            'blueprint'   => $blueprint,
            'rows'        => $rows,
            'entry'       => $entry,
            'hash'        => $shown['hash'] ?? '',
            'status'      => $shown['status'],
            'publishedAt' => $shown['publishedAt'] ?? '',
            'errors'      => $errors,
            'notice'      => $shown['notice'] ?? null,
            'conflict'    => $shown['conflict'] ?? false,
            'canPublish'  => auth()->user()->can('content.publish'),
            'canDelete'   => $canDelete,
            'below'       => $canDelete ? $this->entryStore()->descendantCounts($collection, [$entry->id])[$entry->id] : 0,
        ]);
    }
}
