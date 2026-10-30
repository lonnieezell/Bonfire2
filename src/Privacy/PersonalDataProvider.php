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
 * A module's part in a user's data export and erasure. Register one
 * with service('personalData')->register() from the module's initAdmin().
 */
interface PersonalDataProvider
{
    /**
     * What the data is, for people reading the export.
     */
    public function label(): string;

    /**
     * Everything this module holds about the user, ready for JSON.
     */
    public function export(User $user): array;

    /**
     * Deletes or anonymises everything this module holds about the user.
     */
    public function erase(User $user): void;
}
