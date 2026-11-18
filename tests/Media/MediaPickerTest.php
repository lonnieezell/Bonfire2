<?php

namespace Tests\Media;

use Bonfire\Media\Models\MediaModel;
use Bonfire\Users\User;
use CodeIgniter\Test\TestResponse;
use Config\Services;
use DOMDocument;
use DOMXPath;
use Tests\Support\Media\MediaTestCase;

/**
 * The picker a file or image field opens: the library's files to pick from, and an upload form.
 *
 * @internal
 */
final class MediaPickerTest extends MediaTestCase
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

        Services::injectMock('mediaStorage', $this->storage());

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

    public function testAnImagePickerListsOnlyImages(): void
    {
        $photo = $this->mediaFile('cat.jpg');
        $pdf   = $this->mediaFile('report.pdf', mime: 'application/pdf');

        $images = $this->actingAs($this->admin)->get(route_to('media-picker') . '?type=image');
        $images->assertOK();
        $this->assertSame([$photo->id], $this->pickable($images));

        $files = $this->actingAs($this->admin)->get(route_to('media-picker') . '?type=file');
        $this->assertEqualsCanonicalizing([$photo->id, $pdf->id], $this->pickable($files));
    }

    public function testThePickerOpensOnTheFolderAndSearches(): void
    {
        $cat = $this->mediaFile('cat.jpg', 'pets');
        $this->mediaFile('dog.jpg', 'pets');
        $this->mediaFile('car.jpg', 'vehicles');

        $response = $this->actingAs($this->admin)->get(route_to('media-picker') . '?type=image&folder=pets&q=cat');

        $this->assertSame([$cat->id], $this->pickable($response));
    }

    public function testThePickerRefusesUsersWithoutMediaAccess(): void
    {
        $user = $this->createUser();
        $user->addPermission('admin.access');

        $response = $this->actingAs($user)->get(route_to('media-picker'));

        $response->assertRedirect();
        $response->assertSessionHas('error', lang('Bonfire.notAuthorized'));
    }

    public function testTheUploadFormIsHiddenWithoutMediaEdit(): void
    {
        $viewer = $this->createUser();
        $viewer->addPermission('admin.access', 'media.access');

        $readOnly = $this->actingAs($viewer)->get(route_to('media-picker'));
        $readOnly->assertOK();
        $this->assertSame(0, $this->xpath($readOnly)->query('//form[@data-picker-upload]')->length);

        $editor = $this->actingAs($this->admin)->get(route_to('media-picker'));
        $this->assertSame(1, $this->xpath($editor)->query('//form[@data-picker-upload]')->length);
    }

    public function testAnUploadFromThePickerGoesBackToThePickerInItsFolder(): void
    {
        $this->filesToUpload(['new.jpg']);

        $response = $this->actingAs($this->admin)->post(route_to('media-upload'), ['folder' => 'pets', 'picker' => 'image', csrf_token() => csrf_hash()]);

        $response->assertRedirectTo(site_url(route_to('media-picker') . '?type=image&folder=pets'));
        $this->assertSame(['new.jpg'], array_column(model(MediaModel::class)->asArray()->findAll(), 'filename'));
    }

    public function testAnImagePickerRefusesToUploadAFileThatIsNotAnImage(): void
    {
        $this->filesToUpload(['notes.jpg'], 'plain text, whatever the name says');

        $response = $this->actingAs($this->admin)->post(route_to('media-upload'), ['folder' => '', 'picker' => 'image', csrf_token() => csrf_hash()]);

        $response->assertSessionHas('error');
        $this->assertSame(0, model(MediaModel::class)->countAllResults());
    }

    /**
     * The IDs of the files the picker offers.
     *
     * @return list<string>
     */
    private function pickable(TestResponse $response): array
    {
        $ids = [];

        foreach ($this->xpath($response)->query('//*[@data-pick]') as $button) {
            $ids[] = $button->getAttribute('data-id');
        }

        return $ids;
    }

    private function xpath(TestResponse $response): DOMXPath
    {
        $dom = new DOMDocument();
        @$dom->loadHTML($response->getBody());

        return new DOMXPath($dom);
    }
}
