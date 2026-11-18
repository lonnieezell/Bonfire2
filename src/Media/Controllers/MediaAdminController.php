<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Media\Controllers;

use Bonfire\Core\AdminController;
use Bonfire\Media\Entities\Media;
use Bonfire\Media\Fields\ImageField;
use Bonfire\Media\MediaException;
use Bonfire\Media\Models\MediaModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\Exceptions\RedirectException;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * The media library in the admin: a browser of the files, an upload form, and a form for a file's alt text and folder.
 * Every screen needs media.access, changing anything media.edit, and deleting media.delete.
 */
class MediaAdminController extends AdminController
{
    protected $theme      = 'Admin';
    protected $viewPrefix = 'Bonfire\Media\Views\\';

    /**
     * Turns away anyone without media.access.
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->require('media.access');
    }

    /**
     * The files, newest first, in one folder (`?folder=`, empty for those in none) or all of them,
     * and those whose name or alt text has `?q=`.
     */
    public function list(): string
    {
        $model   = model(MediaModel::class);
        $folders = $model->folders();

        [$folder, $search] = $this->filter($model);

        return $this->render($this->viewPrefix . 'media_list', [
            'files'     => $model->orderBy('created_at', 'desc')->orderBy('path')->paginate(setting('Site.perPage')),
            'pager'     => $model->pager,
            'folders'   => $folders,
            'folder'    => $folder,
            'search'    => $search,
            'canEdit'   => auth()->user()->can('media.edit'),
            'canDelete' => auth()->user()->can('media.delete'),
            'query'     => $this->request->getUri()->getQuery(),
        ]);
    }

    /**
     * The body of the picker a file or image field opens: the files to pick from, filtered like the list, with only
     * images for `?type=image`, and an upload form for those who can upload. It has no layout, as htmx swaps it in.
     */
    public function picker(): string
    {
        $type    = $this->request->getGet('type') === 'image' ? 'image' : 'file';
        $model   = model(MediaModel::class);
        $folders = $model->folders();

        [$folder, $search] = $this->filter($model);

        if ($type === 'image') {
            $model->like('mime', ImageField::MIME_PREFIX, 'after');
        }

        return view($this->viewPrefix . 'picker', [
            'files'   => $model->orderBy('created_at', 'desc')->orderBy('path')->paginate(setting('Site.perPage')),
            'pager'   => $model->pager,
            'type'    => $type,
            'folders' => $folders,
            'folder'  => $folder,
            'search'  => $search,
            'canEdit' => auth()->user()->can('media.edit'),
        ], ['saveData' => false]);
    }

    /**
     * Stores each file in `files[]` under the posted folder. A file that is refused doesn't stop the others.
     * Posted from a field's picker (`picker` is the field's type), it goes back to the picker in that folder.
     */
    public function upload(): RedirectResponse
    {
        $this->require('media.edit');

        $folder   = MediaModel::cleanFolder((string) $this->request->getPost('folder'));
        $picker   = $this->request->getPost('picker');
        $redirect = redirect()->to(in_array($picker, ['file', 'image'], true)
            ? route_to('media-picker') . '?' . http_build_query(['type' => $picker, 'folder' => $folder])
            : route_to('media-list') . '?folder=' . rawurlencode($folder));

        if (mb_strlen($folder) > 255) {
            return $redirect->with('error', lang('Media.folderTooLong'));
        }

        $storage  = service('mediaStorage');
        $errors   = [];
        $uploaded = 0;

        foreach ($this->request->getFileMultiple('files') ?? [] as $file) {
            // An empty slot in the form is no file at all.
            if ($file->getError() === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            try {
                $storage->store($file, 'public', $folder);
                $uploaded++;
            } catch (MediaException $e) {
                $errors[] = $e->getMessage();
            }
        }

        if ($uploaded > 0) {
            $redirect->with('message', lang('Media.uploaded', [$uploaded]));
        }

        if ($errors === [] && $uploaded === 0) {
            $errors[] = lang('Media.noFiles');
        }

        // The alert shows the message as HTML, and the errors hold the names the files were uploaded with.
        return $errors === [] ? $redirect : $redirect->with('error', esc(implode(' ', $errors)));
    }

    /**
     * The form for a file's alt text and folder.
     */
    public function edit(string $id): string
    {
        $this->require('media.edit');

        return $this->renderForm($this->find($id));
    }

    /**
     * Saves a file's alt text and folder. The file stays where it is on disk, so its URL doesn't change.
     *
     * @return RedirectResponse|string
     */
    public function update(string $id)
    {
        $this->require('media.edit');

        $media = $this->find($id);
        $rules = [
            'alt'    => ['label' => lang('Media.alt'), 'rules' => 'permit_empty|max_length[255]'],
            'folder' => ['label' => lang('Media.folder'), 'rules' => 'permit_empty|max_length[255]'],
        ];

        if (! $this->validate($rules)) {
            return $this->renderForm($media, $this->validator->getErrors());
        }

        $alt = trim((string) $this->request->getPost('alt'));

        model(MediaModel::class)->update($media->id, [
            'alt'    => $alt === '' ? null : $alt,
            'folder' => (string) $this->request->getPost('folder'),
        ]);

        return redirect()->to(route_to('media-edit', $media->id))->with('message', lang('Bonfire.resourceSaved', [lang('Media.file')]));
    }

    /**
     * Sends a file to the Recycler. The file stays on disk until it is purged there.
     */
    public function delete(string $id): RedirectResponse
    {
        $this->require('media.delete');

        model(MediaModel::class)->delete($this->find($id)->id);

        // Back to the folder, search and page the list was showing, which a card's form posts as `return`.
        parse_str((string) $this->request->getPost('return'), $return);
        $query = http_build_query(array_intersect_key($return, array_flip(['folder', 'q', 'page'])));

        return redirect()->to(route_to('media-list') . ($query === '' ? '' : '?' . $query))->with('message', lang('Media.deleted'));
    }

    /**
     * Keeps the files in one folder (`?folder=`, empty for those in none) and those whose name or alt text has `?q=`.
     *
     * @return array{string|null, string} the folder, null for all of them, and the search
     */
    private function filter(MediaModel $model): array
    {
        $folder = $this->request->getGet('folder');
        $folder = is_string($folder) ? MediaModel::cleanFolder($folder) : null;
        $search = trim((string) $this->request->getGet('q'));

        if ($folder !== null) {
            $model->where('folder', $folder);
        }

        if ($search !== '') {
            $model->matching($search);
        }

        return [$folder, $search];
    }

    /**
     * @throws RedirectException when the user lacks the permission
     */
    private function require(string $permission): void
    {
        if (! auth()->user()->can($permission)) {
            throw new RedirectException(redirect()->to(ADMIN_AREA)->with('error', lang('Bonfire.notAuthorized')));
        }
    }

    /**
     * @throws PageNotFoundException
     */
    private function find(string $id): Media
    {
        return model(MediaModel::class)->find($id) ?? throw PageNotFoundException::forPageNotFound();
    }

    /**
     * @param array<string, string> $errors
     */
    private function renderForm(Media $media, array $errors = []): string
    {
        helper('number');

        return $this->render($this->viewPrefix . 'media_form', [
            'media'     => $media,
            'errors'    => $errors,
            'canDelete' => auth()->user()->can('media.delete'),
        ]);
    }
}
