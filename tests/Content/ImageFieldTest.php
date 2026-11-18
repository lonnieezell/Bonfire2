<?php

namespace Tests\Content;

use Bonfire\Content\Entry;
use Bonfire\Content\Models\ContentEntryModel;
use Bonfire\Media\Entities\Media;
use Bonfire\Media\Models\MediaModel;
use Bonfire\Users\User;
use CodeIgniter\Test\TestResponse;
use Config\Services;
use DOMDocument;
use DOMXPath;
use Tests\Support\Content\ContentTestCase;

/**
 * An image field in a blueprint: the entry keeps the picked file's ID in its front matter.
 *
 * @internal
 */
final class ImageFieldTest extends ContentTestCase
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

        file_put_contents(
            $this->blueprints . 'collections/blog/post.yaml',
            "title: Post\nfields:\n  - handle: title\n    type: text\n    validate: required\n  - handle: slug\n    type: slug\n  - handle: photo\n    type: image\n    folder: posts\n",
        );

        $this->admin = $this->createUser();
        $this->admin->addGroup('superadmin');
    }

    protected function tearDown(): void
    {
        foreach (['auth', 'bonfire', 'menus', 'widgets'] as $service) {
            Services::resetSingle($service);
        }

        parent::tearDown();
    }

    public function testABlueprintImageFieldSavesItsIdToFrontMatterAndShowsTheFile(): void
    {
        $photo = $this->image();

        $edit = $this->saveEntryWith($photo->id);

        $this->assertSame([$photo->id], $this->query($edit, '//input[@type="hidden"][@name="fields[photo]"]/@value'));
        $this->assertSame([$photo->url()], $this->query($edit, '//*[@data-field="photo"]//img/@src'), 'the picked image is shown');
        $this->assertSame(
            [url_to('media-picker') . '?' . http_build_query(['type' => 'image', 'folder' => 'posts'])],
            $this->query($edit, '//*[@data-field="photo"]//*[@hx-get]/@hx-get'),
            'the picker opens on images in the field\'s folder',
        );
    }

    public function testAFieldWhoseFileWasDeletedShowsItIsMissingAndWontSave(): void
    {
        $photo = $this->image();
        $edit  = $this->saveEntryWith($photo->id);
        model(MediaModel::class)->delete($photo->id);

        $edit = $this->actingAs($this->admin)->get($this->editUrl());

        $this->assertCount(1, $this->query($edit, '//*[@data-field="photo"]//*[@data-missing]'));

        $refused = $this->actingAs($this->admin)->post($this->editUrl(), [
            'blueprint' => 'post',
            'fields'    => ['title' => 'Written', 'slug' => 'written', 'photo' => $photo->id],
        ]);

        $this->assertCount(1, $this->query($refused, '//*[@data-error="photo"]'));
    }

    private function image(): Media
    {
        $model = model(MediaModel::class);

        return $model->find($model->insert([
            'container' => 'public', 'folder' => 'posts', 'path' => 'cat.jpg', 'filename' => 'cat.jpg',
            'mime'      => 'image/jpeg', 'size' => 5, 'width' => 30, 'height' => 20,
        ]));
    }

    /**
     * Creates the entry with the photo, checks its front matter, and opens its edit form.
     */
    private function saveEntryWith(string $photoId): TestResponse
    {
        $this->actingAs($this->admin)->post(route_to('content-new', 'blog'), [
            'blueprint' => 'post',
            'fields'    => ['title' => 'Written', 'photo' => $photoId],
        ])->assertRedirect();

        $this->assertSame($photoId, Entry::fromRow($this->row())->data['photo']);

        return $this->actingAs($this->admin)->get($this->editUrl());
    }

    /**
     * @return array<string, mixed>
     */
    private function row(): array
    {
        return (new ContentEntryModel())->where('slug', 'written')->first();
    }

    private function editUrl(): string
    {
        return route_to('content-edit', 'blog', $this->row()['id']);
    }

    /**
     * @return list<string>
     */
    private function query(TestResponse $response, string $query): array
    {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $response->getBody());
        libxml_clear_errors();

        $values = [];

        foreach ((new DOMXPath($dom))->query($query) as $node) {
            $values[] = $node->nodeValue;
        }

        return $values;
    }
}
