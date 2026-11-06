<?php

namespace Tests\Content;

use Bonfire\Content\Blueprint;
use Bonfire\Content\ContentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\Content\ContentTestCase;

/**
 * @internal
 */
final class BlueprintTest extends ContentTestCase
{
    protected $refresh = false;
    protected $migrate = false;

    public function testItLoadsFromTheCollectionsBlueprintFolder(): void
    {
        $blueprint = Blueprint::load($this->blueprints, 'blog', 'post');

        $this->assertSame('Post', $blueprint->title);
        $this->assertSame(['title', 'slug', 'tagline', 'summary', 'featured', 'event_date', 'body'], array_column($blueprint->fields, 'handle'));
        $this->assertSame('required|max_length[60]', $blueprint->field('title')->validate);
        $this->assertSame('Title', $blueprint->field('title')->label);
        $this->assertSame('Event date', $blueprint->field('event_date')->label);
        $this->assertSame('One line under the title.', $blueprint->field('tagline')->instructions);
        $this->assertNull($blueprint->field('nope'));
    }

    public function testAFieldKeepsTheKeysItsTypeReads(): void
    {
        $this->writeBlueprint("fields:\n  - handle: when\n    type: date\n    time: true\n    validate: [required, valid_date]\n");

        $field = Blueprint::load($this->blueprints, 'blog', 'custom')->field('when');

        $this->assertSame(['time' => true], $field->options);
        $this->assertSame(['required', 'valid_date'], $field->validate);
    }

    #[DataProvider('provideAnInvalidBlueprintIsRefused')]
    public function testAnInvalidBlueprintIsRefused(string $yaml, string $message): void
    {
        $this->writeBlueprint($yaml);

        $this->expectException(ContentException::class);
        $this->expectExceptionMessageMatches($message);

        Blueprint::load($this->blueprints, 'blog', 'custom');
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideAnInvalidBlueprintIsRefused(): iterable
    {
        yield 'not YAML' => ["fields: [unclosed\n", '/Invalid YAML/'];

        yield 'no fields' => ["title: Empty\n", '/needs a `fields` list/'];

        yield 'fields is a map' => ["fields:\n  title:\n    type: text\n", '/needs a `fields` list/'];

        yield 'no handle' => ["fields:\n  - type: text\n", '/needs a `handle`/'];

        yield 'bad handle' => ["fields:\n  - handle: 'a b'\n    type: text\n", '/needs a `handle`/'];

        yield 'reserved handle' => ["fields:\n  - handle: status\n    type: text\n", '/managed by the entry form/'];

        yield 'unknown type' => ["fields:\n  - handle: a\n    type: wysiwyg\n", '/needs a `type`: text, textarea, markdown, toggle, date/'];

        yield 'duplicate handle' => ["fields:\n  - handle: a\n    type: text\n  - handle: a\n    type: text\n", '/two fields named `a`/'];

        yield 'bad validate' => ["fields:\n  - handle: a\n    type: text\n    validate: {required: true}\n", '/`validate` must be/'];

        yield 'fields and sections' => ["fields:\n  - handle: a\n    type: text\nsections:\n  - title: S\n    fields:\n      - handle: b\n        type: text\n", '/both `fields` and `sections`/'];

        yield 'sections is a map' => ["sections:\n  main:\n    title: S\n", '/needs a `sections` list/'];

        yield 'section without a title' => ["sections:\n  - fields:\n      - handle: a\n        type: text\n", '/Section 1 .* needs a `title`/'];

        yield 'section without fields' => ["sections:\n  - title: S\n", '/Section 1 .*needs a `fields` list/'];

        yield 'duplicate handle across sections' => [
            "sections:\n  - title: A\n    fields:\n      - handle: a\n        type: text\n  - title: B\n    fields:\n      - handle: a\n        type: text\n",
            '/two fields named `a`/',
        ];

        yield 'zero columns' => ["sections:\n  - title: S\n    columns: 0\n    fields: []\n", '/Section 1 .*`columns` must be a whole number from 1 to 3/'];

        yield 'four columns' => ["sections:\n  - title: S\n    columns: 4\n    fields: []\n", '/`columns` must be a whole number from 1 to 3/'];

        yield 'columns that is not a number' => ["sections:\n  - title: S\n    columns: two\n    fields: []\n", '/`columns` must be a whole number from 1 to 3/'];

        yield 'a field in a section that is bad' => ["sections:\n  - title: S\n    fields:\n      - type: text\n", '/Field 1 of section 1 of blueprint custom needs a `handle`/'];

        yield 'width other than full' => ["fields:\n  - handle: a\n    type: text\n    width: half\n", '/`width` can only be `full`/'];
    }

    public function testSectionsGroupTheFieldsAndTheBlueprintKeepsThemFlat(): void
    {
        $this->useSections();

        $blueprint = Blueprint::load($this->blueprints, 'blog', 'post');

        $this->assertSame(['title', 'slug', 'tagline', 'summary', 'featured', 'event_date', 'body'], array_column($blueprint->fields, 'handle'));
        $this->assertCount(2, $blueprint->sections);
        $this->assertSame(['Main', 'Details'], array_column($blueprint->sections, 'title'));
        $this->assertSame('What readers see first.', $blueprint->sections[0]->instructions);
        $this->assertNull($blueprint->sections[1]->instructions);
        $this->assertSame([1, 2], array_column($blueprint->sections, 'columns'), 'columns default to 1');
        $this->assertSame(['title', 'slug', 'tagline', 'summary'], array_column($blueprint->sections[0]->fields, 'handle'));
        $this->assertSame(['featured', 'event_date', 'body'], array_column($blueprint->sections[1]->fields, 'handle'));
        $this->assertSame($blueprint->field('tagline'), $blueprint->sections[0]->fields[2]);
    }

    public function testABlueprintOfPlainFieldsHasNoSections(): void
    {
        $this->assertSame([], Blueprint::load($this->blueprints, 'blog', 'post')->sections);
    }

    public function testAFieldCanSpanTheRow(): void
    {
        $this->writeBlueprint("fields:\n  - handle: a\n    type: text\n    width: full\n  - handle: b\n    type: text\n");

        $blueprint = Blueprint::load($this->blueprints, 'blog', 'custom');

        $this->assertTrue($blueprint->field('a')->full);
        $this->assertFalse($blueprint->field('b')->full);
        $this->assertSame([], $blueprint->field('a')->options, 'width is not an option of the type');
    }

    public function testTheSeoMappingCanNameAFieldInASection(): void
    {
        $this->useSections();
        file_put_contents($this->blueprints . 'collections/blog/post.yaml', "seo:\n  title: tagline\n", FILE_APPEND);

        $this->assertSame(['title' => 'tagline'], Blueprint::load($this->blueprints, 'blog', 'post')->seo);
    }

    public function testAMissingOrUnsafeBlueprintIsUnknown(): void
    {
        foreach ([['blog', 'nope'], ['blog', '../post'], ['../blog', 'post'], ['blog', '']] as [$collection, $handle]) {
            try {
                Blueprint::load($this->blueprints, $collection, $handle);
                $this->fail("{$collection}/{$handle} loaded");
            } catch (ContentException $e) {
                $this->assertStringStartsWith('Unknown blueprint', $e->getMessage());
            }
        }
    }

    private function writeBlueprint(string $yaml): void
    {
        file_put_contents($this->blueprints . 'collections/blog/custom.yaml', $yaml);
    }
}
