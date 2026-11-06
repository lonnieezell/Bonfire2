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
