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
        $folder = $this->request->getGet('folder');
        $search = trim((string) $this->request->getGet('q'));
        $model  = model(MediaModel::class);
        $query  = model(MediaModel::class, false);

        if (is_string($folder)) {
            $query->where('folder', $this->folder($folder));
        }

        if ($search !== '') {
            $query->matching($search);
        }

        return $this->render($this->viewPrefix . 'media_list', [
            'files'   => $query->orderBy('created_at', 'desc')->orderBy('path')->paginate(setting('Site.perPage')),
            'pager'   => $query->pager,
            'folders' => $model->folders(),
            'folder'  => is_string($folder) ? $this->folder($folder) : null,
            'search'  => $search,
            'canEdit' => auth()->user()->can('media.edit'),
        ]);
    }

    /**
     * Stores each file in `files[]` under the posted folder. A file that is refused doesn't stop the others.
     */
    public function upload(): RedirectResponse
    {
        $this->require('media.edit');

        $folder   = $this->folder((string) $this->request->getPost('folder'));
        $storage  = service('mediaStorage');
        $errors   = [];
        $uploaded = 0;

        foreach ($this->request->getFileMultiple('files') ?? [] as $file) {
            try {
                $storage->store($file, 'public', $folder);
                $uploaded++;
            } catch (MediaException $e) {
                $errors[] = $e->getMessage();
            }
        }

        $redirect = redirect()->to(route_to('media-list') . '?folder=' . rawurlencode($folder));

        if ($errors !== []) {
            return $redirect->with('error', implode(' ', $errors));
        }

        return $uploaded === 0
            ? $redirect->with('error', lang('Media.noFiles'))
            : $redirect->with('message', lang('Media.uploaded', [$uploaded]));
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
            'folder' => $this->folder((string) $this->request->getPost('folder')),
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

        return redirect()->to(route_to('media-list'))->with('message', lang('Media.deleted'));
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
     * A folder as typed, without the spaces and slashes around it.
     */
    private function folder(string $folder): string
    {
        return trim($folder, " /\t");
    }

    /**
     * @param array<string, string> $errors
     */
    private function renderForm(Media $media, array $errors = []): string
    {
        return $this->render($this->viewPrefix . 'media_form', [
            'media'     => $media,
            'errors'    => $errors,
            'canDelete' => auth()->user()->can('media.delete'),
        ]);
    }
}
