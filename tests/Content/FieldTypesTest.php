<?php

namespace Tests\Content;

use Bonfire\Content\Fields\SlugField;
use Bonfire\Fields\Field;
use Bonfire\Fields\FieldTypes;
use Bonfire\Fields\Types\DateField;
use Bonfire\Fields\Types\MarkdownField;
use Bonfire\Fields\Types\TextareaField;
use Bonfire\Fields\Types\TextField;
use Bonfire\Fields\Types\ToggleField;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class FieldTypesTest extends TestCase
{
    protected $migrate = false;

    public function testEveryTypeHasAClassAndABundledPartial(): void
    {
        $this->assertSame(['text', 'textarea', 'markdown', 'toggle', 'date', 'datetime', 'select', 'integer', 'slug', 'entries', 'terms'], FieldTypes::names());

        foreach (FieldTypes::names() as $type) {
            // The generic types render from Bonfire\Fields, and the ones tied to an entry from Content.
            $module = in_array($type, ['slug', 'entries', 'terms'], true) ? 'Content' : 'Fields';

            $this->assertSame($type, FieldTypes::get($type)->partial());
            $this->assertSame("Bonfire\\{$module}\\Views\\fields\\{$type}", FieldTypes::view($type));
            $this->assertFileExists(dirname(__DIR__, 2) . "/src/{$module}/Views/fields/{$type}.php");
        }
    }

    public function testTheRulesAreTheBlueprintsValidateWithTheTypesOwnAdded(): void
    {
        $this->assertSame('required|max_length[5]', (new TextField())->rules($this->field('text', 'required|max_length[5]')));
        $this->assertSame(['required'], (new TextField())->rules($this->field('text', ['required'])));
        $this->assertSame('required|permit_empty|valid_date[Y-m-d]', (new DateField())->rules($this->field('date', 'required')));
        $this->assertSame('permit_empty|valid_date[Y-m-d H:i:s]', (new DateField())->rules($this->field('date', '', ['time' => true])));
        $this->assertSame(['a', 'permit_empty', 'valid_date[Y-m-d]'], (new DateField())->rules($this->field('date', ['a'])));
        $this->assertSame('required|max_length[100]', (new SlugField())->rules($this->field('slug')));
    }

    public function testTextTypesTakeOnlyText(): void
    {
        $field = $this->field('text');

        $this->assertSame('', (new TextField())->prepare(['a'], $field, []));
        $this->assertSame('', (new TextField())->prepare(null, $field, []));
        $this->assertSame("a\r\nb", (new TextField())->prepare("a\r\nb", $field, []));
        $this->assertSame("a\nb", (new TextareaField())->prepare("a\r\nb", $field, []));
        $this->assertSame("a\nb", (new MarkdownField())->prepare("a\r\nb", $field, []));
    }

    public function testTextCastsToAndFromStored(): void
    {
        $field = $this->field('text');

        $this->assertSame('Hi', (new TextField())->toStored('Hi', $field));
        $this->assertSame('42', (new TextField())->fromStored(42, $field));
        $this->assertSame('', (new TextField())->fromStored(null, $field));
        $this->assertSame('', (new TextField())->fromStored(['a'], $field));
    }

    public function testToggleCastsToABoolean(): void
    {
        $field  = $this->field('toggle');
        $toggle = new ToggleField();

        $this->assertSame('1', $toggle->prepare('1', $field, []));
        $this->assertSame('1', $toggle->prepare('on', $field, []));
        $this->assertSame('0', $toggle->prepare('0', $field, []));
        $this->assertSame('0', $toggle->prepare(null, $field, []), 'a switch that is off posts nothing');
        $this->assertTrue($toggle->toStored('1', $field));
        $this->assertFalse($toggle->toStored('0', $field));
        $this->assertSame('1', $toggle->fromStored(true, $field));
        $this->assertSame('0', $toggle->fromStored(false, $field));
        $this->assertSame('0', $toggle->fromStored(null, $field));
    }

    #[DataProvider('provideDateIsKeptInOneForm')]
    public function testDateIsKeptInOneForm(string $input, bool $time, string $expected): void
    {
        $this->assertSame($expected, DateField::normalize($input, $time));
    }

    /**
     * @return iterable<string, array{string, bool, string}>
     */
    public static function provideDateIsKeptInOneForm(): iterable
    {
        yield 'date' => ['2026-10-27', false, '2026-10-27'];

        yield 'date to date and time' => ['2026-10-27', true, '2026-10-27 00:00:00'];

        yield 'browser date and time' => ['2026-10-27T09:30', true, '2026-10-27 09:30:00'];

        yield 'with seconds' => ['2026-10-27T09:30:15', true, '2026-10-27 09:30:15'];

        yield 'space separated' => ['2026-10-27 09:30:15', true, '2026-10-27 09:30:15'];

        yield 'time dropped from a date' => ['2026-10-27 09:30:15', false, '2026-10-27'];

        yield 'not a date is left for validation' => [' next week ', true, 'next week'];
    }

    public function testDateCastsToAndFromStored(): void
    {
        $date = new DateField();

        $this->assertNull($date->toStored('', $this->field('date')));
        $this->assertSame('2026-10-27', $date->toStored('2026-10-27', $this->field('date')));
        $this->assertSame('2026-10-27', $date->fromStored('2026-10-27 00:00:00', $this->field('date')));
        $this->assertSame('2026-10-27 09:30:00', $date->fromStored('2026-10-27 09:30:00', $this->field('date', '', ['time' => true])));
        $this->assertSame('', $date->fromStored(null, $this->field('date')));
    }

    public function testSlugIsMadeFromTheTitleUnlessTyped(): void
    {
        $field = $this->field('slug');
        $slug  = new SlugField();

        $this->assertFalse($slug->storedInFrontMatter());
        $this->assertSame('hello-world', $slug->prepare('', $field, ['fields' => ['title' => 'Hello, World!']]));
        $this->assertSame('typed-slug', $slug->prepare('  Typed Slug ', $field, ['fields' => ['title' => 'Hello']]));
        $this->assertSame('', $slug->prepare('', $field, ['fields' => []]));
        $this->assertSame('', $slug->prepare('', $field, ['fields' => ['title' => ['x']]]));
        $this->assertSame('from-name', $slug->prepare('', $this->field('slug', '', ['from' => 'name']), ['fields' => ['title' => 'No', 'name' => 'From Name']]));
        $this->assertSame(100, strlen($slug->prepare(str_repeat('a', 150), $field, [])));
        $this->assertSame('ab', $slug->prepare('a/../b', $field, []), 'a slug never holds a path');
    }

    /**
     * @param list<string>|string  $validate
     * @param array<string, mixed> $options
     */
    private function field(string $type, array|string $validate = '', array $options = []): Field
    {
        return new Field('f', $type, 'F', $validate, null, $options);
    }
}
