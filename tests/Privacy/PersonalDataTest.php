<?php

namespace Tests\Privacy;

use Bonfire\Privacy\PersonalData;
use Bonfire\Privacy\PersonalDataProvider;
use Bonfire\Users\User;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class PersonalDataTest extends TestCase
{
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

    /**
     * @param list<mixed> $export
     */
    private function provider(string $label, array $export): PersonalDataProvider
    {
        return new class ($label, $export) implements PersonalDataProvider {
            public function __construct(private string $label, private array $export)
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
