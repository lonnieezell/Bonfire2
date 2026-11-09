<?php

namespace Tests\Fields;

use Bonfire\Fields\FieldTypes;
use Bonfire\Fields\Types\TextField;
use Tests\Support\Content\ShoutField;
use Tests\Support\TestCase;

/**
 * Where the field types come from and where their input partials render from.
 *
 * @internal
 */
final class FieldTypesTest extends TestCase
{
    protected $migrate = false;

    public function testAPartialInTheAppsFieldsFolderOverridesATypesInput(): void
    {
        $dir  = APPPATH . 'Views/fields';
        $file = $dir . '/text.php';
        $made = ! is_dir($dir) && mkdir($dir, 0777, true);

        $this->assertSame('Bonfire\Fields\Views\fields\text', FieldTypes::view('text'));

        file_put_contents($file, '<input data-overridden>');

        try {
            $this->assertSame('App\Views\fields\text', FieldTypes::view('text'));
            $this->assertStringContainsString('data-overridden', view(FieldTypes::view('text')));
            $this->assertSame('Bonfire\Fields\Views\fields\toggle', FieldTypes::view('toggle'), 'only the overridden type changes');
        } finally {
            unlink($file);

            if ($made) {
                rmdir($dir);
            }
        }
    }

    public function testTheOldContentOverrideFolderIsNotLookedAt(): void
    {
        $dir  = APPPATH . 'Views/content/fields';
        $file = $dir . '/text.php';
        $made = ! is_dir($dir) && mkdir($dir, 0777, true);
        file_put_contents($file, '<input data-overridden>');

        try {
            $this->assertSame('Bonfire\Fields\Views\fields\text', FieldTypes::view('text'));
        } finally {
            unlink($file);

            if ($made) {
                rmdir($dir);
                @rmdir(APPPATH . 'Views/content');
            }
        }
    }

    public function testTypesRegisteredInConfigAreAddedToTheBuiltInOnes(): void
    {
        $this->assertFalse(FieldTypes::has('shout'));

        config('Fields')->types = ['shout' => ShoutField::class];

        $this->assertTrue(FieldTypes::has('shout'));
        $this->assertInstanceOf(ShoutField::class, FieldTypes::get('shout'));
        $this->assertContains('text', FieldTypes::names());
        $this->assertContains('slug', FieldTypes::names(), 'a replaced $types leaves the types other modules add');
    }

    public function testAHandleThatABuiltInTypeHasIsTakenOverByTheOneInConfig(): void
    {
        config('Fields')->types = ['text' => ShoutField::class];

        $this->assertInstanceOf(ShoutField::class, FieldTypes::get('text'));

        config('Fields')->types = [];

        $this->assertInstanceOf(TextField::class, FieldTypes::get('text'));
    }

    public function testAClassThatIsNotAFieldTypeIsLeftOut(): void
    {
        config('Fields')->types = ['shout' => self::class, 'ghost' => 'No\Such\Class'];

        $this->assertFalse(FieldTypes::has('shout'));
        $this->assertFalse(FieldTypes::has('ghost'));
    }

    public function testContentsTypesComeInThroughTheRegistrarNotTheGenericList(): void
    {
        $this->assertSame(
            ['slug' => 'Bonfire\Content\Fields\SlugField', 'entries' => 'Bonfire\Content\Fields\EntriesField', 'terms' => 'Bonfire\Content\Fields\TermsField', 'blocks' => 'Bonfire\Content\Fields\BlocksField', 'list' => 'Bonfire\Content\Fields\ListField'],
            config('Fields')->moduleTypes,
        );
    }
}
