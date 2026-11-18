<?php

namespace Tests\Media;

use Bonfire\Fields\Field;
use Bonfire\Fields\FieldException;
use Bonfire\Fields\FieldTypes;
use Bonfire\Fields\Form;
use Bonfire\Media\Models\MediaModel;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\Media\MediaTestCase;

/**
 * The file and image field types: one media file, picked from the library, stored as its ID.
 *
 * @internal
 */
final class FileFieldTest extends MediaTestCase
{
    #[DataProvider('types')]
    public function testStoresThePickedId(string $type): void
    {
        $photo = $this->mediaFile('cat.jpg');
        $form  = $this->form($type);

        $values = $form->prepare(['fields' => ['photo' => " {$photo->id} "]]);

        $this->assertSame([], $form->validate($values));
        $this->assertSame($photo->id, FieldTypes::get($type)->toStored($values['photo'], $this->field($type)));
    }

    #[DataProvider('types')]
    public function testAnEmptyOptionalFieldStoresNull(string $type): void
    {
        $form   = $this->form($type);
        $values = $form->prepare(['fields' => ['photo' => '']]);

        $this->assertSame([], $form->validate($values));
        $this->assertNull(FieldTypes::get($type)->toStored($values['photo'], $this->field($type)));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function types(): iterable
    {
        yield 'file' => ['file'];

        yield 'image' => ['image'];
    }

    #[DataProvider('refused')]
    public function testRefusesAnIdThatIsNotALiveFileOfTheType(string $type, string $case): void
    {
        $id = match ($case) {
            'unknown' => '6f1c2a54-0000-4000-8000-000000000000',
            'deleted' => $this->deletedFile(),
            'pdf'     => $this->mediaFile('report.pdf', mime: 'application/pdf')->id,
        };

        $form   = $this->form($type);
        $errors = $form->validate($form->prepare(['fields' => ['photo' => $id]]));

        $this->assertArrayHasKey('photo', $errors);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function refused(): iterable
    {
        yield 'file, an unknown id' => ['file', 'unknown'];

        yield 'file, a deleted file' => ['file', 'deleted'];

        yield 'image, an unknown id' => ['image', 'unknown'];

        yield 'image, a deleted file' => ['image', 'deleted'];

        yield 'image, a file that is not an image' => ['image', 'pdf'];
    }

    public function testAFileFieldTakesAFileThatIsNotAnImage(): void
    {
        $pdf  = $this->mediaFile('report.pdf', mime: 'application/pdf');
        $form = $this->form('file');

        $this->assertSame([], $form->validate($form->prepare(['fields' => ['photo' => $pdf->id]])));
    }

    public function testAFolderThatIsNotTextIsRefused(): void
    {
        $this->expectException(FieldException::class);

        FieldTypes::get('image')->check(new Field('photo', 'image', 'Photo', '', null, ['folder' => ['pets']]));
    }

    private function deletedFile(): string
    {
        $media = $this->mediaFile('gone.jpg');
        model(MediaModel::class)->delete($media->id);

        return $media->id;
    }

    private function form(string $type): Form
    {
        return new Form([$this->field($type)]);
    }

    private function field(string $type): Field
    {
        return new Field('photo', $type, 'Photo', '', null, []);
    }
}
