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
            ->register(new TestWidget('test:narrow', null, new SizeRange(Size::Small, Size::Small, Size::Medium)));
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
            ['title' => '', 'widgets' => [['id' => 'test:b', 'size' => 'lg'], ['id' => 'test:a', 'size' => 'sm']]],
        ]]);

        $this->assertSame(['test:b', 'test:a'], $layout->ids());
        $this->assertSame(Size::Large, $layout->sections[0]->widgets[0]->size);
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

        $this->assertSame(Size::Medium, $layout->sections[0]->widgets[0]->size);
    }

    public function testASizeOutsideTheWidgetsRangeFallsBackToItsDefault(): void
    {
        $layout = $this->validator->validate(['version' => 1, 'sections' => [
            ['title' => '', 'widgets' => [['id' => 'test:narrow', 'size' => 'full'], ['id' => 'test:a', 'size' => 'full']]],
        ]]);

        $this->assertSame(Size::Small, $layout->sections[0]->widgets[0]->size);
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
            ['title' => '', 'widgets' => [['id' => 'test:a', 'size' => 'sm']]],
        ]]);

        $this->assertSame(
            ['version' => 1, 'sections' => [['title' => '', 'widgets' => [['id' => 'test:a', 'size' => 'sm']]]]],
            $layout->toArray(),
        );
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
            'widget no id'     => [['version' => 1, 'sections' => [['title' => '', 'widgets' => [['size' => 'sm']]]]]],
        ];
    }
}
