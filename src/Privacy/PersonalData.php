<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Privacy;

use Bonfire\Users\User;

/**
 * The modules that hold personal data, for exporting and erasing
 * a user's data in one go. Available as service('personalData').
 */
class PersonalData
{
    /**
     * @var array<string, PersonalDataProvider>
     */
    private array $providers = [];

    public function register(string $alias, PersonalDataProvider $provider): self
    {
        $this->providers[$alias] = $provider;

        return $this;
    }

    /**
     * @return array<string, PersonalDataProvider>
     */
    public function providers(): array
    {
        return $this->providers;
    }

    /**
     * Every provider's data about the user, keyed by alias.
     *
     * @return array<string, array>
     */
    public function export(User $user): array
    {
        return array_map(static fn (PersonalDataProvider $provider) => $provider->export($user), $this->providers);
    }
}
