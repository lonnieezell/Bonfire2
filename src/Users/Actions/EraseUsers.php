<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Users\Actions;

use Bonfire\Actions\Action;
use Bonfire\Actions\ActionContext;
use Bonfire\Actions\ActionResult;
use Bonfire\Actions\Confirm;
use Bonfire\Users\Libraries\UserAccess;
use Bonfire\Users\User;

/**
 * Erases users' personal data with every registered provider, leaving
 * anonymous rows behind (ADR 0006). Who may be erased follows the same
 * rules as deleting.
 */
class EraseUsers extends Action
{
    public string $icon       = 'fas fa-user-slash';
    public string $permission = 'users.erase';
    public bool $danger       = true;

    public function __construct()
    {
        $this->label = lang('Users.eraseUsers');
    }

    public function allows(User $actor, object $record): bool
    {
        return (new UserAccess($actor))->canSelectForDeletion($record);
    }

    public function confirm(): ?Confirm
    {
        return Confirm::make(lang('Users.eraseConfirm'))
            ->body(lang('Users.eraseConfirmBody'))
            ->button(lang('Users.eraseUsers'))
            ->typeToConfirm();
    }

    public function handle(ActionContext $context): ActionResult
    {
        foreach ($context->entities() as $user) {
            service('personalData')->erase($user);
        }

        return ActionResult::message(lang('Users.erased'));
    }
}
