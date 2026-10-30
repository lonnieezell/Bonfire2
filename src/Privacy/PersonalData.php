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
use Closure;
use Throwable;

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

    /**
     * Work to do once an erasure has committed, like deleting files.
     *
     * @var list<Closure>
     */
    private array $afterErase = [];

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

    /**
     * Erases the user's data with every provider in one transaction,
     * the `users` provider last so the others can still read the user.
     * If any provider throws, nothing is changed.
     */
    public function erase(User $user): void
    {
        $providers = $this->providers;

        if (isset($providers['users'])) {
            $users = $providers['users'];
            unset($providers['users']);
            $providers['users'] = $users;
        }

        $db = db_connect();
        $db->transBegin();

        try {
            foreach ($providers as $provider) {
                $provider->erase($user);
            }

            $db->transCommit();
        } catch (Throwable $e) {
            $db->transRollback();
            $this->afterErase = [];

            throw $e;
        }

        $callbacks        = $this->afterErase;
        $this->afterErase = [];

        foreach ($callbacks as $callback) {
            $callback();
        }
    }

    /**
     * Runs $callback once the current erasure has committed, so a file
     * is only deleted when the data pointing at it is gone too. Providers
     * call it on service('personalData') from their erase().
     */
    public function afterErase(Closure $callback): void
    {
        $this->afterErase[] = $callback;
    }
}
