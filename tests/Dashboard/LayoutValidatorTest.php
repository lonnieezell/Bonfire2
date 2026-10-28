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
            ->register(new TestWidget('test:b'));
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
            'widget no id'     => [['version' => 1, 'sections' => [['title' => '', 'widgets' => [['size' => 'sm']]]]]],
        ];
    }
}
