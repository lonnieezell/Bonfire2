<?php

namespace Tests\Privacy;

use Bonfire\Privacy\PersonalData;
use Bonfire\Privacy\PersonalDataProvider;
use Bonfire\Users\User;
use Closure;
use RuntimeException;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class PersonalDataTest extends TestCase
{
    protected $refresh = true;

    /**
     * @var list<string>
     */
    private array $calls = [];

    public function testTheServiceIsShared(): void
    {
        $this->assertInstanceOf(PersonalData::class, service('personalData'));
        $this->assertSame(service('personalData'), service('personalData'));
    }

    public function testExportHoldsEachProvidersDataUnderItsAlias(): void
    {
        $data = new PersonalData();
        $data->register('orders', $this->provider('Orders', ['total' => 3]));
        $data->register('posts', $this->provider('Posts', [['title' => 'Hello']]));

        $user     = new User();
        $user->id = 7;

        $this->assertSame(
            ['orders' => ['total' => 3], 'posts' => [['title' => 'Hello']]],
            $data->export($user),
        );
    }

    public function testListsTheRegisteredProviders(): void
    {
        $data   = new PersonalData();
        $orders = $this->provider('Orders', []);
        $data->register('orders', $orders);

        $this->assertSame(['orders' => $orders], $data->providers());
    }

    public function testErasesWithEveryProviderAndTheUsersProviderLast(): void
    {
        $data = new PersonalData();
        $data->register('users', $this->eraser('users'));
        $data->register('orders', $this->eraser('orders'));
        $data->register('posts', $this->eraser('posts'));

        $data->erase(new User());

        $this->assertSame(['orders', 'posts', 'users'], $this->calls);
    }

    public function testFilesAreDeletedOnlyAfterTheErasureCommits(): void
    {
        $data = new PersonalData();
        $data->register('posts', $this->eraser('posts', function () use ($data): void {
            $data->afterErase(function (): void {
                $this->calls[] = 'file deleted';
            });
        }));
        $data->register('users', $this->eraser('users'));

        $data->erase(new User());

        $this->assertSame(['posts', 'users', 'file deleted'], $this->calls);
    }

    public function testNothingChangesWhenAProviderFails(): void
    {
        $data = new PersonalData();
        $data->register('posts', $this->eraser('posts', function () use ($data): void {
            db_connect()->table('consent_records')->insert(['consent_id' => 'x', 'consents' => '{}']);
            $data->afterErase(function (): void {
                $this->calls[] = 'file deleted';
            });
        }));
        $data->register('users', $this->eraser('users', static function (): void {
            throw new RuntimeException('Failed');
        }));

        try {
            $data->erase(new User());
            $this->fail('The failure was swallowed');
        } catch (RuntimeException $e) {
            $this->assertSame('Failed', $e->getMessage());
        }

        $this->dontSeeInDatabase('consent_records', ['consent_id' => 'x']);
        $this->assertNotContains('file deleted', $this->calls);

        // Callbacks from a failed erasure don't run after a later one
        $data->register('users', $this->eraser('users'));
        $data->register('posts', $this->eraser('posts'));
        $data->erase(new User());
        $this->assertNotContains('file deleted', $this->calls);
    }

    private function eraser(string $alias, ?Closure $erase = null): PersonalDataProvider
    {
        $calls = &$this->calls;

        return new class ($alias, $calls, $erase) implements PersonalDataProvider {
            private array $calls;

            public function __construct(private readonly string $alias, array &$calls, private readonly ?Closure $erase)
            {
                $this->calls = &$calls;
            }

            public function label(): string
            {
                return $this->alias;
            }

            public function export(User $user): array
            {
                return [];
            }

            public function erase(User $user): void
            {
                $this->calls[] = $this->alias;

                if ($this->erase instanceof Closure) {
                    ($this->erase)();
                }
            }
        };
    }

    /**
     * @param list<mixed> $export
     */
    private function provider(string $label, array $export): PersonalDataProvider
    {
        return new class ($label, $export) implements PersonalDataProvider {
            public function __construct(private readonly string $label, private readonly array $export)
            {
            }

            public function label(): string
            {
                return $this->label;
            }

            public function export(User $user): array
            {
                return $user->id === 7 ? $this->export : [];
            }

            public function erase(User $user): void
            {
            }
        };
    }
}
