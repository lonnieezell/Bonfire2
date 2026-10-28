<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Tests\Dashboard;

use Bonfire\Dashboard\LayoutValidator;
use Bonfire\Dashboard\Size;
use Bonfire\Dashboard\SizeRange;
use Bonfire\Dashboard\WidgetField;
use Bonfire\Dashboard\WidgetRegistry;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\Libraries\TestWidget;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class LayoutValidatorTest extends TestCase
{
    private LayoutValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        setting()->flush();
        setting('Dashboard.cells', []);
        $registry = (new WidgetRegistry())
            ->register(new TestWidget('test:a'))
            ->register(new TestWidget('test:b'))
            ->register(new TestWidget('test:narrow', null, new SizeRange(Size::Quarter, Size::Quarter, Size::Half)))
            ->register(new TestWidget('test:configurable', null, new SizeRange(Size::Half), [
                WidgetField::toggle('compact', 'Compact', default: false),
                WidgetField::number('days', 'Days', default: 7, min: 1, max: 31),
                WidgetField::choice('order', 'Order', ['asc' => 'Oldest first', 'desc' => 'Newest first'], default: 'desc'),
                WidgetField::text('caption', 'Caption', default: ''),
            ]));
        $this->validator = new LayoutValidator($registry);
    }

    protected function tearDown(): void
    {
        setting()->flush();

        parent::tearDown();
    }

    public function testKeepsAValidLayout(): void
    {
        $layout = $this->validator->validate(['version' => 1, 'sections' => [
            ['title' => '', 'widgets' => [['id' => 'test:b', 'size' => 'two-thirds'], ['id' => 'test:a', 'size' => 'quarter']]],
        ]]);

        $this->assertSame(['test:b', 'test:a'], $layout->ids());
        $this->assertSame(Size::TwoThirds, $layout->sections[0]->widgets[0]->size);
    }

    public function testDropsUnknownAndDuplicateIds(): void
    {
        $layout = $this->validator->validate(['version' => 1, 'sections' => [
            ['title' => '', 'widgets' => [['id' => 'test:a'], ['id' => 'test:gone'], ['id' => 'test:a'], ['id' => 'test:b']]],
        ]]);

        $this->assertSame(['test:a', 'test:b'], $layout->ids());
    }

    public function testDropsUnavailableItems(): void
    {
        setting('Stats.Stats_usersInRecycler693', false);

        $layout = (new LayoutValidator(new WidgetRegistry()))->validate(['version' => 1, 'sections' => [
            ['title' => '', 'widgets' => [['id' => 'stats:usersInRecycler693']]],
        ]]);

        $this->assertSame([], $layout->ids());
    }

    public function testAnUnknownSizeFallsBackToTheWidgetDefault(): void
    {
        $layout = $this->validator->validate(['version' => 1, 'sections' => [
            ['title' => '', 'widgets' => [['id' => 'test:a', 'size' => 'huge']]],
        ]]);

        $this->assertSame(Size::Half, $layout->sections[0]->widgets[0]->size);
    }

    public function testASizeOutsideTheWidgetsRangeFallsBackToItsDefault(): void
    {
        $layout = $this->validator->validate(['version' => 1, 'sections' => [
            ['title' => '', 'widgets' => [['id' => 'test:narrow', 'size' => 'full'], ['id' => 'test:a', 'size' => 'full']]],
        ]]);

        $this->assertSame(Size::Quarter, $layout->sections[0]->widgets[0]->size);
        $this->assertSame(Size::Full, $layout->sections[0]->widgets[1]->size);
    }

    public function testKeepsSectionsAndTheirTitles(): void
    {
        $layout = $this->validator->validate(['version' => 1, 'sections' => [
            ['title' => '  Overview ', 'widgets' => [['id' => 'test:a']]],
            ['title' => '', 'widgets' => [['id' => 'test:b']]],
        ]]);

        $this->assertSame(['Overview', ''], array_map(static fn ($section) => $section->title, $layout->sections));
    }

    public function testTrimsLongTitles(): void
    {
        $layout = $this->validator->validate(['version' => 1, 'sections' => [
            ['title' => str_repeat('é', 150), 'widgets' => [['id' => 'test:a']]],
        ]]);

        $this->assertSame(str_repeat('é', 100), $layout->sections[0]->title);
    }

    public function testDropsEmptySections(): void
    {
        $layout = $this->validator->validate(['version' => 1, 'sections' => [
            ['title' => 'Empty', 'widgets' => []],
            ['title' => 'Only gone widgets', 'widgets' => [['id' => 'test:gone']]],
            ['title' => 'Kept', 'widgets' => [['id' => 'test:a']]],
        ]]);

        $this->assertSame(['Kept'], array_map(static fn ($section) => $section->title, $layout->sections));
    }

    public function testRefusesMoreThanTwentySections(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->validator->validate(['version' => 1, 'sections' => array_fill(0, 21, ['title' => '', 'widgets' => [['id' => 'test:a']]])]);
    }

    public function testStoresAsTheVersionOneFormat(): void
    {
        $layout = $this->validator->validate(['version' => 1, 'sections' => [
            ['title' => '', 'widgets' => [['id' => 'test:a', 'size' => 'quarter']]],
        ]]);

        $this->assertSame(
            ['version' => 1, 'sections' => [['title' => '', 'widgets' => [['id' => 'test:a', 'size' => 'quarter']]]]],
            $layout->toArray(),
        );
    }

    public function testKeepsDeclaredSettingsThatDifferFromTheirInheritedValue(): void
    {
        $settings = ['compact' => true, 'days' => 14, 'order' => 'asc', 'caption' => 'Mine'];

        $layout = $this->validator->validate(['version' => 1, 'sections' => [
            ['title' => '', 'widgets' => [['id' => 'test:configurable', 'size' => 'half', 'settings' => $settings]]],
        ]]);

        $this->assertSame(
            ['version' => 1, 'sections' => [['title' => '', 'widgets' => [['id' => 'test:configurable', 'size' => 'half', 'settings' => $settings]]]]],
            $layout->toArray(),
        );
    }

    /**
     * @param array<string, mixed>|null $stored
     */
    #[DataProvider('provideStoresOnlyTheWidgetsOwnSettings')]
    public function testStoresOnlyTheWidgetsOwnSettings(mixed $posted, ?array $stored, string $id = 'test:configurable'): void
    {
        $layout = $this->validator->validate(['version' => 1, 'sections' => [
            ['title' => '', 'widgets' => [['id' => $id, 'settings' => $posted]]],
        ]]);

        $this->assertSame($stored, $layout->toArray()['sections'][0]['widgets'][0]['settings'] ?? null);
    }

    public static function provideStoresOnlyTheWidgetsOwnSettings(): iterable
    {
        return [
            'undeclared keys'         => [['colour' => 'red', 'days' => 14], ['days' => 14]],
            'not an object'           => ['days=14', null],
            'a list'                  => [[14], null],
            'numbers clamped'         => [['days' => 99], ['days' => 31]],
            'numeric text cast'       => [['days' => '3'], ['days' => 3]],
            'not a number'            => [['days' => 'many'], null],
            'a choice not offered'    => [['order' => 'random'], null],
            'toggle from form text'   => [['compact' => 'on'], ['compact' => true]],
            'not a toggle'            => [['compact' => 'perhaps'], null],
            'text trimmed'            => [['caption' => '  Mine  '], ['caption' => 'Mine']],
            'text too long'           => [['caption' => str_repeat('x', 300)], ['caption' => str_repeat('x', 255)]],
            'not text'                => [['caption' => ['Mine']], null],
            'same as inherited'       => [['days' => 7, 'order' => 'desc', 'compact' => false], null],
            'a widget without fields' => [['days' => 14], null, 'test:a'],
        ];
    }

    public function testASettingEqualToTheSiteWideValueItInheritsIsNotStored(): void
    {
        setting('Dashboard.testDays', 14);
        $registry = (new WidgetRegistry())->register(new TestWidget('test:keyed', null, new SizeRange(Size::Half), [
            WidgetField::number('days', 'Days', default: 7, key: 'Dashboard.testDays'),
        ]));

        $layout = (new LayoutValidator($registry))->validate(['version' => 1, 'sections' => [
            ['title' => '', 'widgets' => [['id' => 'test:keyed', 'settings' => ['days' => 14]]]],
        ]]);

        $this->assertSame([], $layout->sections[0]->widgets[0]->values);
    }

    #[DataProvider('provideRejectsAMalformedPayload')]
    public function testRejectsAMalformedPayload(mixed $payload): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->validator->validate($payload);
    }

    public static function provideRejectsAMalformedPayload(): iterable
    {
        return [
            'not an array'     => ['layout'],
            'wrong version'    => [['version' => 2, 'sections' => []]],
            'no version'       => [['sections' => []]],
            'sections missing' => [['version' => 1]],
            'section not list' => [['version' => 1, 'sections' => ['x']]],
            'widgets not list' => [['version' => 1, 'sections' => [['title' => '', 'widgets' => 'x']]]],
            'title not text'   => [['version' => 1, 'sections' => [['title' => ['x'], 'widgets' => []]]]],
            'widget no id'     => [['version' => 1, 'sections' => [['title' => '', 'widgets' => [['size' => 'quarter']]]]]],
        ];
    }
}
