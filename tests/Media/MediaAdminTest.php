<?php

namespace Tests\Media;

use Bonfire\Media\MediaStorage;
use Bonfire\Media\Models\MediaModel;
use Bonfire\Users\User;
use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\Test\TestResponse;
use Config\Services;
use Tests\Support\Media\MediaTestCase;

/**
 * @internal
 */
final class MediaAdminTest extends MediaTestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        setting('Site.siteOnline', true);

        foreach (['auth', 'bonfire', 'menus', 'widgets'] as $service) {
            Services::resetSingle($service);
        }

        service('routes')->loadRoutes();

        // PHP only moves files it received as a real HTTP upload.
        Services::injectMock('mediaStorage', new class () extends MediaStorage {
            protected function moveUpload(UploadedFile $file, string $directory, string $name): bool
            {
                return rename($file->getTempName(), $directory . '/' . $name);
            }
        });

        $this->admin = $this->createUser();
        $this->admin->addGroup('superadmin');
    }

    protected function tearDown(): void
    {
        service('superglobals')->setFilesArray([]);

        foreach (['auth', 'bonfire', 'menus', 'widgets', 'mediaStorage'] as $service) {
            Services::resetSingle($service);
        }

        parent::tearDown();
    }

    public function testListsFilesInTheChosenFolder(): void
    {
        $cat = $this->mediaFile('cat.jpg', 'pets');
        $dog = $this->mediaFile('dog.jpg', 'pets');
        $car = $this->mediaFile('car.jpg', 'vehicles');
        $top = $this->mediaFile('top.jpg');

        $all = $this->actingAs($this->admin)->get(route_to('media-list'));
        $all->assertOK();
        $this->assertEqualsCanonicalizing([$cat->id, $dog->id, $car->id, $top->id], $this->listed($all));
        $this->assertSame(['pets', 'vehicles'], $this->folders($all));

        $pets = $this->actingAs($this->admin)->get(route_to('media-list') . '?folder=pets');
        $this->assertEqualsCanonicalizing([$cat->id, $dog->id], $this->listed($pets));

        $root = $this->actingAs($this->admin)->get(route_to('media-list') . '?folder=');
        $this->assertSame([$top->id], $this->listed($root));
    }

    public function testSearchMatchesFilenameAndAltText(): void
    {
        $byName = $this->mediaFile('sunset-beach.jpg');
        $byAlt  = $this->mediaFile('img-001.jpg', '', 'A beach at dawn');
        $this->mediaFile('mountain.jpg', '', 'Snow');

        $response = $this->actingAs($this->admin)->get(route_to('media-list') . '?q=beach');

        $this->assertEqualsCanonicalizing([$byName->id, $byAlt->id], $this->listed($response));
    }

    public function testUploadStoresTheFilesInTheChosenFolder(): void
    {
        $this->filesToUpload(['one.jpg', 'two.txt']);

        $response = $this->actingAs($this->admin)->post(route_to('media-upload'), ['folder' => 'docs', csrf_token() => csrf_hash()]);

        $response->assertRedirectTo(site_url(route_to('media-list') . '?folder=docs'));
        $rows = model(MediaModel::class)->orderBy('path')->findAll();
        $this->assertSame([['docs', 'one.jpg'], ['docs', 'two.txt']], array_map(static fn ($row): array => [$row->folder, $row->path], $rows));
        $this->assertFileExists($this->dir . 'one.jpg');
        $this->assertFileExists($this->dir . 'two.txt');
    }

    public function testUploadReportsTheFilesItRefusedAndKeepsTheOthers(): void
    {
        $this->filesToUpload(['one.jpg', 'evil.php']);

        $response = $this->actingAs($this->admin)->post(route_to('media-upload'), ['folder' => '', csrf_token() => csrf_hash()]);

        $response->assertSessionHas('error', lang('Media.extensionNotAllowed', ['evil.php']));
        $this->assertSame(['one.jpg'], array_column(model(MediaModel::class)->asArray()->findAll(), 'path'));
    }

    public function testEditSavesAltTextAndMovesTheFolderWithoutChangingTheUrl(): void
    {
        $media = $this->mediaFile('cat.jpg', 'pets');
        $url   = $media->url();

        $response = $this->actingAs($this->admin)->post(route_to('media-edit', $media->id), [
            'alt'        => 'A sleeping cat',
            'folder'     => 'animals',
            csrf_token() => csrf_hash(),
        ]);

        $response->assertRedirectTo(site_url(route_to('media-edit', $media->id)));
        $saved = model(MediaModel::class)->find($media->id);
        $this->assertSame(['A sleeping cat', 'animals', $url], [$saved->alt, $saved->folder, $saved->url()]);
        $this->assertFileExists($this->dir . 'cat.jpg');
    }

    public function testDeleteSoftDeletesTheFile(): void
    {
        $media = $this->mediaFile('cat.jpg');

        $this->actingAs($this->admin)->post(route_to('media-delete', $media->id), [csrf_token() => csrf_hash()])->assertRedirect();

        $this->assertNull(model(MediaModel::class)->find($media->id));
        $this->assertNotNull(model(MediaModel::class)->withDeleted()->find($media->id));
        $this->assertFileExists($this->dir . 'cat.jpg');
    }

    public function testTheRecyclerListsADeletedFile(): void
    {
        $media = $this->mediaFile('cat.jpg');
        model(MediaModel::class)->delete($media->id);

        $this->actingAs($this->admin)->get(route_to('recycler') . '?r=media')->assertSee(route_to('recycler-purge', 'media', $media->id));
    }

    public function testPurgingADeletedFileRemovesItFromDisk(): void
    {
        $media = $this->mediaFile('cat.jpg');
        $kept  = $this->mediaFile('dog.jpg');
        model(MediaModel::class)->delete([$media->id, $kept->id]);

        $this->actingAs($this->admin)->post(route_to('recycler-purge', 'media', $media->id), [csrf_token() => csrf_hash()])->assertSessionMissing('error');

        $this->assertNull(model(MediaModel::class)->withDeleted()->find($media->id));
        $this->assertFileDoesNotExist($this->dir . 'cat.jpg');
        $this->assertFileExists($this->dir . 'dog.jpg');
    }

    public function testPurgeRefusesAFileThatIsNotDeleted(): void
    {
        $media = $this->mediaFile('cat.jpg');

        $this->actingAs($this->admin)->post(route_to('recycler-purge', 'media', $media->id), [csrf_token() => csrf_hash()])->assertSessionHas('error');

        $this->assertNotNull(model(MediaModel::class)->find($media->id));
        $this->assertFileExists($this->dir . 'cat.jpg');
    }

    public function testRestoringBringsItBackToTheList(): void
    {
        $media = $this->mediaFile('cat.jpg');
        model(MediaModel::class)->delete($media->id);

        $this->actingAs($this->admin)->post(route_to('recycler-restore', 'media', $media->id), [csrf_token() => csrf_hash()])->assertSessionMissing('error');

        $this->assertSame([$media->id], $this->listed($this->actingAs($this->admin)->get(route_to('media-list'))));
    }

    public function testRefusesUploadEditAndDeleteWithoutThePermissions(): void
    {
        $media = $this->mediaFile('cat.jpg', '', 'Cat');
        $user  = $this->createUser();
        $user->addPermission('admin.access', 'media.access');
        $this->filesToUpload(['one.jpg']);

        $this->actingAs($user)->get(route_to('media-list'))->assertOK();

        $attempts = [
            ['get', route_to('media-edit', $media->id)],
            ['post', route_to('media-edit', $media->id)],
            ['post', route_to('media-upload')],
            ['post', route_to('media-delete', $media->id)],
        ];

        foreach ($attempts as [$method, $path]) {
            $response = $this->actingAs($user)->{$method}($path, ['alt' => 'Hacked', 'folder' => 'x', csrf_token() => csrf_hash()]);

            $response->assertRedirectTo(site_url(ADMIN_AREA));
            $response->assertSessionHas('error', lang('Bonfire.notAuthorized'));
        }

        $this->assertSame(['Cat', ''], [model(MediaModel::class)->find($media->id)->alt, model(MediaModel::class)->find($media->id)->folder]);
        $this->assertSame(1, model(MediaModel::class)->countAllResults());
    }

    public function testRefusesThePageWithoutMediaAccess(): void
    {
        $user = $this->createUser();
        $user->addPermission('admin.access');

        $response = $this->actingAs($user)->get(route_to('media-list'));

        $response->assertRedirectTo(site_url(ADMIN_AREA));
        $response->assertSessionHas('error', lang('Bonfire.notAuthorized'));
    }

    public function testTheSidebarLinksToTheLibraryForThoseWithMediaAccess(): void
    {
        $user = $this->createUser();
        $user->addPermission('admin.access');

        $this->actingAs($this->admin)->get(ADMIN_AREA)->assertSee(url_to('media-list'));
        $this->actingAs($user)->get(ADMIN_AREA)->assertDontSee(url_to('media-list'));
    }

    /**
     * Puts files into $_FILES['files'] as a multiple upload.
     *
     * @param list<string> $names
     */
    private function filesToUpload(array $names): void
    {
        $files = ['name' => [], 'type' => [], 'tmp_name' => [], 'error' => [], 'size' => []];

        foreach ($names as $name) {
            $path = tempnam(sys_get_temp_dir(), 'bf-media');
            imagejpeg(imagecreatetruecolor(4, 4), $path);

            $files['name'][]     = $name;
            $files['type'][]     = 'image/jpeg';
            $files['tmp_name'][] = $path;
            $files['error'][]    = UPLOAD_ERR_OK;
            $files['size'][]     = filesize($path);
        }

        service('superglobals')->setFilesArray(['files' => $files]);
    }

    /**
     * @return list<string> the ids of the files the page lists
     */
    private function listed(TestResponse $response): array
    {
        preg_match_all('/data-media="([^"]+)"/', $response->getBody(), $matches);

        return $matches[1];
    }

    /**
     * @return list<string> the folders the page offers
     */
    private function folders(TestResponse $response): array
    {
        preg_match_all('/data-folder="([^"]+)"/', $response->getBody(), $matches);

        return $matches[1];
    }
}
